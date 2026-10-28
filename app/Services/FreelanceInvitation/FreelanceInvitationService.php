<?php

namespace App\Services\FreelanceInvitation;

use App\Exceptions\ApiException;
use App\Models\DevProfile;
use App\Models\FreelanceInvitation;
use App\Traits\InteractsWithFreelanceJobVacancies;
use Illuminate\Support\Facades\DB;

class FreelanceInvitationService
{
    use InteractsWithFreelanceJobVacancies;

    /**
     * O cliente convida um dev (em geral um dos recomendados) para orçar o freela
     */
    public function store(array $data): FreelanceInvitation
    {
        $freelanceJobVacancy = $this->findClientVacancy($data['id']);

        if (!$freelanceJobVacancy->acceptsProposals()) {
            throw new ApiException("Only open freelance job vacancies can receive invitations!", 422);
        }

        $devProfile = DevProfile::findOrFail($data['dev_profile_id']);

        if ($freelanceJobVacancy->invitations()->where('dev_profile_id', $devProfile->id)->exists()) {
            throw new ApiException("This developer has already been invited to this freelance job vacancy!", 422);
        }

        if ($freelanceJobVacancy->proposals()->where('dev_profile_id', $devProfile->id)->exists()) {
            throw new ApiException("This developer already sent a proposal for this freelance job vacancy!", 422);
        }

        $clientName = $freelanceJobVacancy->clientProfile?->name;

        return DB::transaction(function () use ($freelanceJobVacancy, $devProfile, $clientName) {

            $invitation = $freelanceJobVacancy->invitations()->create([
                'dev_profile_id' => $devProfile->id
            ]);

            $devProfile->notifications()->create([
                'type' => 'freelance_invitation_received',
                'title' => 'Convite para orçamento',
                'message' => "{$clientName} convidou você para enviar um orçamento para o freela {$freelanceJobVacancy->title}.",
                'link' => $this->freelanceJobVacancyLink($freelanceJobVacancy)
            ]);

            return $invitation->load('devProfile.user');
        });
    }
}
