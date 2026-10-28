<?php

namespace App\Http\Controllers\AcademicBackground;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicBackground\DeleteAcademicBackgroundRequest;
use App\Http\Requests\AcademicBackground\IndexAcademicBackgroundRequest;
use App\Http\Requests\AcademicBackground\StoreAcademicBackgroundRequest;
use App\Http\Requests\AcademicBackground\UpdateAcademicBackgroundRequest;
use App\Services\AcademicBackground\AcademicBackgroundService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class AcademicBackgroundController extends Controller
{
    public function __construct(
        protected AcademicBackgroundService $academicBackgroundService
    ){}

    #[Endpoint(operationId: 'indexAcademicBackground', title: 'Listar formações acadêmicas', description: '**operationId:** `indexAcademicBackground` — Lista paginada das formações acadêmicas, com filtros opcionais por `dev_profile_id`, `search` (busca em `degree`, `degree_level` e `institution`) e `verified` (apenas formações com certificado anexado). Em **200**, `data.data[]` segue o schema **Academic Background Resource** (`App\\Http\\Resources\\AcademicBackground\\AcademicBackgroundResource`) e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexAcademicBackgroundRequest $request): JsonResponse
    {
        try {
            $academicBackgrounds = $this->academicBackgroundService->index($request->validated());

            return ApiResponse::success($academicBackgrounds, "Academic backgrounds listed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeAcademicBackground', title: 'Cadastrar formação acadêmica', description: '**operationId:** `storeAcademicBackground` — Cadastra uma formação acadêmica para o perfil de desenvolvedor autenticado. O arquivo `certificate` é opcional e, quando enviado, é anexado à coleção de mídia `certificate`. Dispara a tradução do conteúdo e a regeração do embedding do perfil. Em **201**, `data` segue o schema **Academic Background Resource** (`App\\Http\\Resources\\AcademicBackground\\AcademicBackgroundResource`).')]
    public function store(StoreAcademicBackgroundRequest $request): JsonResponse
    {
        try {
            $academicBackground = $this->academicBackgroundService->store($request->validated());

            return ApiResponse::success($academicBackground, "Academic background registered with success!", 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateAcademicBackground', title: 'Atualizar formação acadêmica', description: '**operationId:** `updateAcademicBackground` — Atualiza uma formação acadêmica existente. Requer que o registro pertença ao perfil autenticado (`AcademicBackgroundPolicy::update`). Quando `degree` é alterado, a tradução é refeita; quando `certificate` é enviado, um novo certificado é anexado. Em **200**, `data` segue o schema **Academic Background Resource** (`App\\Http\\Resources\\AcademicBackground\\AcademicBackgroundResource`).')]
    public function update(UpdateAcademicBackgroundRequest $request): JsonResponse
    {
        try {
            $academicBackground = $this->academicBackgroundService->update($request->validated());

            return ApiResponse::success($academicBackground, "Academic background updated with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'deleteAcademicBackground', title: 'Remover formação acadêmica', description: '**operationId:** `deleteAcademicBackground` — Remove uma formação acadêmica. Requer que o registro pertença ao perfil autenticado (`AcademicBackgroundPolicy::delete`). Também dispara a regeração do embedding do perfil. Em **200**, `data` é `null`.')]
    public function delete(DeleteAcademicBackgroundRequest $request): JsonResponse
    {
        try {
            $this->academicBackgroundService->delete($request->validated());

            return ApiResponse::success(message: "Academic background removed with success!");
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
