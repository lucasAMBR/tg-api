<?php

namespace App\Services\JobVacancyInvitation;

use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Models\CompanyProfile;
use App\Models\DevProfile;
use App\Models\JobVacancy;
use App\Models\JobVacancyInvitation;
use App\Services\Chat\ChatService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class JobVacancyInvitationService
{
    public function __construct(protected ChatService $chatService) {}

    /**
     * A empresa convida um dev (em geral um dos recomendados) para participar do
     * processo seletivo da vaga. O convite chega ao dev como uma mensagem no chat,
     * enviada em nome da empresa, com o link da vaga para ele se candidatar
     */
    public function store(array $data): JobVacancyInvitation
    {
        [$jobVacancy, $companyProfile] = $this->findCompanyJobVacancy($data['id']);

        if (!$jobVacancy->acceptsApplications()) {
            throw new ApiException("Only job vacancies with open inscriptions can receive invitations!", 422);
        }

        $devProfile = DevProfile::findOrFail($data['dev_profile_id']);

        if ($jobVacancy->invitations()->where('dev_profile_id', $devProfile->id)->exists()) {
            throw new ApiException("This developer has already been invited to this job vacancy!", 422);
        }

        if ($jobVacancy->applications()->where('dev_profile_id', $devProfile->id)->exists()) {
            throw new ApiException("This developer has already applied for this job vacancy!", 422);
        }

        $companyUserId = Auth::id();

        return DB::transaction(function () use ($jobVacancy, $companyProfile, $devProfile, $companyUserId) {

            $invitation = $jobVacancy->invitations()->create([
                'dev_profile_id' => $devProfile->id
            ]);

            $this->chatService->sendAutomaticMessage(
                $companyUserId,
                $devProfile->user_id,
                $this->invitationMessage($jobVacancy, $companyProfile, $devProfile)
            );

            return $invitation->load('devProfile.user');
        });
    }

    /**
     * Mensagem do convite, escrita em nome da empresa
     */
    private function invitationMessage(JobVacancy $jobVacancy, CompanyProfile $companyProfile, DevProfile $devProfile): string
    {
        $link = rtrim(config('app.frontend_url'), '/') . "/vacancies/{$jobVacancy->id}";

        return implode("\n\n", [
            "Olá, {$devProfile->name}! Tudo bem?",
            "Somos da {$companyProfile->name} e, ao conhecer o seu perfil, ficamos muito interessados na sua trajetória. Acreditamos que você tem tudo a ver com a nossa vaga de {$jobVacancy->title} e, por isso, gostaríamos de convidar você para participar do nosso processo seletivo.",
            "Você pode conferir todos os detalhes da oportunidade e se candidatar pelo link abaixo:\n{$link}",
            "As inscrições estão abertas e ficaremos muito felizes em contar com a sua candidatura. Se tiver qualquer dúvida, é só responder por aqui.",
            "Atenciosamente,\nEquipe {$companyProfile->name}",
        ]);
    }

    /**
     * Carrega a vaga garantindo que ela pertence à empresa autenticada
     *
     * @return array{0: JobVacancy, 1: CompanyProfile}
     */
    private function findCompanyJobVacancy(string $jobVacancyId): array
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        if (!$authUser->hasRole('company')) {
            throw new ApiException("You must be a company to invite developers!", 403);
        }

        $companyProfile = ProfileHelper::getUserProfileByRole($authUser);

        $jobVacancy = $companyProfile
            ? JobVacancy::query()
                ->where('id', $jobVacancyId)
                ->where('company_profile_id', $companyProfile->id)
                ->first()
            : null;

        if (!$jobVacancy) {
            throw new ApiException("This job vacancy does not belong to your company!", 403);
        }

        return [$jobVacancy, $companyProfile];
    }
}
