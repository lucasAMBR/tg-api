<?php

namespace App\Http\Controllers\JobVacancyInvitation;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\JobVacancyInvitation\StoreJobVacancyInvitationRequest;
use App\Http\Resources\JobVacancyInvitation\JobVacancyInvitationResource;
use App\Services\JobVacancyInvitation\JobVacancyInvitationService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class JobVacancyInvitationController extends Controller
{
    public function __construct(protected JobVacancyInvitationService $jobVacancyInvitationService) {}

    #[Endpoint(operationId: 'storeJobVacancyInvitation', title: 'Convidar dev para o processo seletivo', description: '**operationId:** `storeJobVacancyInvitation` — A empresa dona convida um dev (`dev_profile_id`, em geral um dos devs recomendados) para participar do processo seletivo da vaga. O convite chega ao dev como uma mensagem no chat, enviada em nome da empresa, com o link da vaga no front (`FRONTEND_URL` + `/vacancies/{id}`), e o dev passa a vir com `is_invited: true` na recomendação da vaga. Só vagas com inscrições abertas recebem convites, e é recusado dev já convidado ou que já se candidatou (**422**). Em **201**, `data` segue o schema **Job Vacancy Invitation Resource** (`App\\Http\\Resources\\JobVacancyInvitation\\JobVacancyInvitationResource`).')]
    public function store(StoreJobVacancyInvitationRequest $request): JsonResponse
    {
        try {
            $data = $this->jobVacancyInvitationService->store($request->validated());

            return ApiResponse::success(
                new JobVacancyInvitationResource($data),
                'Developer invited with success!',
                201
            );
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
