<?php

namespace App\Http\Controllers\EmploymentHistory;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmploymentHistory\DeleteEmploymentHistoryRequest;
use App\Http\Requests\EmploymentHistory\IndexEmploymentHistoryRequest;
use App\Http\Requests\EmploymentHistory\StoreEmploymentHistoryRequest;
use App\Http\Requests\EmploymentHistory\UpdateEmploymentHistoryRequest;
use App\Services\EmploymentHistory\EmploymentHistoryService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class EmploymentHistoryController extends Controller
{
    public function __construct(protected EmploymentHistoryService $employmentHistoryService){}

    #[Endpoint(operationId: 'storeEmploymentHistory', title: 'Cadastrar experiência profissional', description: '**operationId:** `storeEmploymentHistory` — Cadastra um item de histórico profissional para o perfil de desenvolvedor autenticado. Administradores precisam estar com o perfil ativo `dev`. Dispara a tradução do conteúdo e a regeração do embedding do perfil. Em **201**, `data` segue o schema **Employment History Resource** (`App\\Http\\Resources\\EmploymentHistory\\EmploymentHistoryResource`).')]
    public function store(StoreEmploymentHistoryRequest $request): JsonResponse
    {
        try {
            $employmentHistory = $this->employmentHistoryService->store($request->validated());

            return ApiResponse::success($employmentHistory, "Employment History item created with sucess!", 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateEmploymentHistory', title: 'Atualizar experiência profissional', description: '**operationId:** `updateEmploymentHistory` — Atualiza um item de histórico profissional existente. Requer que o registro pertença ao perfil autenticado (`EmploymentHistoryPolicy::update`). Quando `position_name` ou `actuation_details` mudam, a tradução é refeita; o embedding do perfil é regerado. Em **200**, `data` segue o schema **Employment History Resource** (`App\\Http\\Resources\\EmploymentHistory\\EmploymentHistoryResource`).')]
    public function update(UpdateEmploymentHistoryRequest $request): JsonResponse
    {
        try {
            $employmentHistory = $this->employmentHistoryService->update($request->validated());

            return ApiResponse::success($employmentHistory, "Employment History item updated with success!", 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'deleteEmploymentHistory', title: 'Remover experiência profissional', description: '**operationId:** `deleteEmploymentHistory` — Remove um item de histórico profissional. Requer que o registro pertença ao perfil autenticado (`EmploymentHistoryPolicy::delete`). Também dispara a regeração do embedding do perfil. Em **200**, `data` é `null`.')]
    public function delete(DeleteEmploymentHistoryRequest $request): JsonResponse
    {
        try {
            $this->employmentHistoryService->delete($request->validated());

            return ApiResponse::success(message: "Employment History item removed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'indexEmploymentHistory', title: 'Listar experiências profissionais', description: '**operationId:** `indexEmploymentHistory` — Lista paginada dos históricos profissionais, ordenada por `start_date` decrescente, com filtros opcionais por `profile_id` e `search` (busca em `position_name`, `company_location` e `company_name`). Em **200**, `data.data[]` segue o schema **Employment History Resource** (`App\\Http\\Resources\\EmploymentHistory\\EmploymentHistoryResource`) e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexEmploymentHistoryRequest $request): JsonResponse
    {
        try {
            $employmentHistory = $this->employmentHistoryService->index($request->validated());

            return ApiResponse::success($employmentHistory, "Employment History indexed with sucess", 200);
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
