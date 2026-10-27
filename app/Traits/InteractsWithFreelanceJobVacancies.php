<?php

namespace App\Traits;

use App\Exceptions\ApiException;
use App\Models\ClientProfile;
use App\Models\DevProfile;
use App\Models\FreelanceJobVacancy;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * O que os services do freela compartilham: quem age em cada ponta (o cliente dono da
 * vaga e o dev que orça ou foi contratado) e o link da vaga usado nas notificações
 */
trait InteractsWithFreelanceJobVacancies
{
    /**
     * Garante que o usuário autenticado é um cliente com perfil criado
     */
    protected function resolveClientProfile(): ClientProfile
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->hasRole('client')) {
            throw new ApiException("You must be a client for manage a freelance job vacancy", 403);
        }

        $clientProfile = $authUser->client_profile;

        if (!$clientProfile) {
            throw new ApiException("This action needs a existent profile to proceed!", 403);
        }

        return $clientProfile;
    }

    /**
     * Garante que o usuário autenticado é um dev com perfil criado
     */
    protected function resolveDevProfile(): DevProfile
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->hasRole('dev')) {
            throw new ApiException("You must be a developer to do this action!", 403);
        }

        $devProfile = $authUser->dev_profile;

        if (!$devProfile) {
            throw new ApiException("This action needs a existent profile to proceed!", 403);
        }

        return $devProfile;
    }

    /**
     * Carrega a vaga garantindo que ela pertence ao cliente autenticado
     */
    protected function findClientVacancy(string $freelanceJobVacancyId): FreelanceJobVacancy
    {
        $clientProfile = $this->resolveClientProfile();

        $freelanceJobVacancy = FreelanceJobVacancy::findOrFail($freelanceJobVacancyId);

        if ($freelanceJobVacancy->client_profile_id !== $clientProfile->id) {
            throw new ApiException("You can only manage your own job vacancies!", 403);
        }

        return $freelanceJobVacancy;
    }

    protected function freelanceJobVacancyLink(FreelanceJobVacancy $freelanceJobVacancy): string
    {
        return config('app.url') . "/freelance-job-vacancy/{$freelanceJobVacancy->id}";
    }
}
