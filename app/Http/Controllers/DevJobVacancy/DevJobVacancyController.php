<?php

namespace App\Http\Controllers\DevJobVacancy;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DevJobVacancy\IndexDevJobVacancyRequest;
use App\Http\Requests\DevJobVacancy\ReviewDevJobVacancyRequest;
use App\Http\Requests\DevJobVacancy\StoreDevJobVacancyRequest;
use App\Services\DevJobVacancy\DevJobVacancyService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class DevJobVacancyController extends Controller
{
    public function __construct(protected DevJobVacancyService $devJobVacancyService) {}

    #[Endpoint(operationId: 'storeDevJobVacancy', title: 'Candidatar-se a uma vaga', description: '**operationId:** `storeDevJobVacancy` — Inscreve o desenvolvedor autenticado na vaga informada, com status inicial `PENDING`. Exige role `dev` e perfil de desenvolvedor existente, e recusa candidaturas duplicadas para a mesma vaga. Em **201**, `data` segue o schema **Dev Job Vacancy Resource** (`App\\Http\\Resources\\DevJobVacancy\\DevJobVacancyResource`), com `jobVacancy` e `devProfile` carregados.')]
    public function apply(StoreDevJobVacancyRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyService->apply($request->validated());

            return ApiResponse::success(
                $data,
                'User registered for the vacancy with success!',
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

    #[Endpoint(operationId: 'indexApplies', title: 'Listar candidaturas das vagas da empresa', description: '**operationId:** `indexApplies` — Lista paginada das candidaturas às vagas do perfil de empresa autenticado (exige role `company`), com filtro opcional `search` (busca no nome do desenvolvedor e no título da vaga). Em **200**, `data.data[]` segue o schema **Dev Job Vacancy Resource** (`App\\Http\\Resources\\DevJobVacancy\\DevJobVacancyResource`) e `data.pagination` traz os metadados de paginação.')]
    public function indexApplies(IndexDevJobVacancyRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyService->indexApplies($request->validated());

            return ApiResponse::success(
                $data,
                'Applies indexed with success!',
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

    #[Endpoint(operationId: 'reviewApply', title: 'Avaliar candidatura', description: '**operationId:** `reviewApply` — Atualiza o `status` de uma candidatura. A atualização só é aplicada quando a vaga pertence ao perfil de empresa autenticado; caso contrário a resposta é **403**. Em **200**, `data` segue o schema **Dev Job Vacancy Resource** (`App\\Http\\Resources\\DevJobVacancy\\DevJobVacancyResource`), com `jobVacancy` e `devProfile` carregados.')]
    public function reviewApply(ReviewDevJobVacancyRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyService->reviewApply($request->validated());

            return ApiResponse::success(
                $data,
                'Apply rate with success!',
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
