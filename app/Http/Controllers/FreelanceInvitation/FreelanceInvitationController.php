<?php

namespace App\Http\Controllers\FreelanceInvitation;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FreelanceInvitation\StoreFreelanceInvitationRequest;
use App\Http\Resources\FreelanceInvitation\FreelanceInvitationResource;
use App\Services\FreelanceInvitation\FreelanceInvitationService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class FreelanceInvitationController extends Controller
{
    public function __construct(protected FreelanceInvitationService $freelanceInvitationService) {}

    #[Endpoint(operationId: 'storeFreelanceInvitation', title: 'Convidar dev para orçar', description: '**operationId:** `storeFreelanceInvitation` — O cliente dono convida um dev (`dev_profile_id`, em geral um dos devs recomendados) para enviar orçamento no freela aberto. O dev é notificado e passa a ver `is_invited: true` no freela. Recusa dev já convidado ou que já orçou. Em **201**, `data` segue o schema **Freelance Invitation Resource** (`App\\Http\\Resources\\FreelanceInvitation\\FreelanceInvitationResource`).')]
    public function store(StoreFreelanceInvitationRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceInvitationService->store($request->validated());

            return ApiResponse::success(
                new FreelanceInvitationResource($data),
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
