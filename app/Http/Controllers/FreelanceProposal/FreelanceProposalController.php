<?php

namespace App\Http\Controllers\FreelanceProposal;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FreelanceProposal\AcceptFreelanceProposalRequest;
use App\Http\Requests\FreelanceProposal\DestroyFreelanceProposalRequest;
use App\Http\Requests\FreelanceProposal\IndexFreelanceProposalRequest;
use App\Http\Requests\FreelanceProposal\IndexMyFreelanceProposalsRequest;
use App\Http\Requests\FreelanceProposal\StoreFreelanceProposalRequest;
use App\Http\Requests\FreelanceProposal\UpdateFreelanceProposalRequest;
use App\Http\Resources\FreelanceProposal\FreelanceProposalResource;
use App\Services\FreelanceProposal\FreelanceProposalService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class FreelanceProposalController extends Controller
{
    public function __construct(protected FreelanceProposalService $freelanceProposalService) {}

    #[Endpoint(operationId: 'indexFreelanceProposals', title: 'Listar orçamentos de um freela', description: '**operationId:** `indexFreelanceProposals` — Lista paginada dos orçamentos recebidos em um freela, do mais recente para o mais antigo, cada um com o perfil público do dev. Só o cliente dono do freela. Aceita o filtro opcional `status` (`pending`, `accepted`, `rejected`). Para os orçamentos ordenados por compatibilidade, use `GET /recommendation/freelance-job-vacancy/{id}/proposals`. Em **200**, `data.data[]` segue o schema **Freelance Proposal Resource** (`App\\Http\\Resources\\FreelanceProposal\\FreelanceProposalResource`) e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexFreelanceProposalRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceProposalService->index($request->validated());

            return ApiResponse::success(
                $data,
                'Freelance proposals indexed with success!',
                200
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

    #[Endpoint(operationId: 'indexMyFreelanceProposals', title: 'Listar meus orçamentos', description: '**operationId:** `indexMyFreelanceProposals` — Lista paginada dos orçamentos enviados pelo dev autenticado (exige role `dev`), do mais recente para o mais antigo, cada um com o seu freela em `freelance_job_vacancy`. Aceita o filtro opcional `status` (`pending`, `accepted`, `rejected`). Em **200**, `data.data[]` segue o schema **Freelance Proposal Resource** (`App\\Http\\Resources\\FreelanceProposal\\FreelanceProposalResource`) e `data.pagination` traz os metadados de paginação.')]
    public function indexMine(IndexMyFreelanceProposalsRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceProposalService->indexMine($request->validated());

            return ApiResponse::success(
                $data,
                'Freelance proposals indexed with success!',
                200
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

    #[Endpoint(operationId: 'storeFreelanceProposal', title: 'Enviar orçamento para um freela', description: '**operationId:** `storeFreelanceProposal` — O dev autenticado (exige role `dev` e perfil criado) envia o orçamento para um freela aberto, de uma das duas formas: `pricing_type: fixed` com o `total_price`, ou `pricing_type: hourly` com o `hourly_rate` e as `estimated_hours` (o `total_price` é calculado). `message` é opcional. Um orçamento por dev em cada freela: para mudar, edite o que já foi enviado. O cliente é notificado. Em **201**, `data` segue o schema **Freelance Proposal Resource** (`App\\Http\\Resources\\FreelanceProposal\\FreelanceProposalResource`).')]
    public function store(StoreFreelanceProposalRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceProposalService->store($request->validated());

            return ApiResponse::success(
                new FreelanceProposalResource($data),
                'Freelance proposal sent with success!',
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

    #[Endpoint(operationId: 'updateFreelanceProposal', title: 'Editar orçamento', description: '**operationId:** `updateFreelanceProposal` — O dev reenvia o próprio orçamento inteiro (`pricing_type` e os valores dele, com as mesmas regras do envio), enquanto ele está pendente e o freela aberto. `message` é opcional: quando não vem, continua a que estava. Em **200**, `data` segue o schema **Freelance Proposal Resource** (`App\\Http\\Resources\\FreelanceProposal\\FreelanceProposalResource`).')]
    public function update(UpdateFreelanceProposalRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceProposalService->update($request->validated());

            return ApiResponse::success(
                new FreelanceProposalResource($data),
                'Freelance proposal updated with success!',
                200
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

    #[Endpoint(operationId: 'deleteFreelanceProposal', title: 'Retirar orçamento', description: '**operationId:** `deleteFreelanceProposal` — O dev retira o próprio orçamento, enquanto ele está pendente e o freela aberto. Depois pode enviar outro. Em **200**, `data` é `null`.')]
    public function destroy(DestroyFreelanceProposalRequest $request): JsonResponse
    {
        try {
            $this->freelanceProposalService->destroy($request->validated());

            return ApiResponse::success(
                null,
                'Freelance proposal deleted with success!',
                200
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

    #[Endpoint(operationId: 'acceptFreelanceProposal', title: 'Aceitar orçamento', description: '**operationId:** `acceptFreelanceProposal` — O cliente dono escolhe um orçamento pendente e fecha o contrato: o orçamento passa a `accepted`, os demais pendentes a `rejected` e o freela a `in_progress`, liberando o feed. O dev escolhido e os recusados são notificados. Em **200**, `data` segue o schema **Freelance Proposal Resource** (`App\\Http\\Resources\\FreelanceProposal\\FreelanceProposalResource`).')]
    public function accept(AcceptFreelanceProposalRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceProposalService->accept($request->validated());

            return ApiResponse::success(
                new FreelanceProposalResource($data),
                'Freelance proposal accepted with success!',
                200
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
