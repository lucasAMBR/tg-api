<?php

namespace App\Http\Controllers\CompanyProjects;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyProjects\DestroyCompanyProjectRequest;
use App\Http\Requests\CompanyProjects\IndexCompanyProjectsRequest;
use App\Http\Requests\CompanyProjects\StoreCompanyProjectRequest;
use App\Http\Requests\CompanyProjects\UpdateCompanyProjectsRequest;
use App\Services\CompanyProjects\CompanyProjectService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class CompanyProjectController extends Controller
{

    protected CompanyProjectService $companyProject;

    public function __construct(CompanyProjectService $companyProject){
        $this->companyProject = $companyProject;
    }

    #[Endpoint(operationId: 'storeCompanyProject', title: 'Cadastrar projeto da empresa', description: '**operationId:** `storeCompanyProject` — Cadastra um projeto para o perfil de empresa autenticado, vinculando as `languages` informadas e disparando a tradução de `title` e `description`. Em **201**, `data` segue o schema **Company Project Resource** (`App\\Http\\Resources\\CompanyProject\\CompanyProjectResource`), já com as linguagens carregadas.')]
    public function store(StoreCompanyProjectRequest $request): JsonResponse {

        try {
            $project = $this->companyProject->store($request->validated());

            return ApiResponse::success($project, 'Project created with success!', 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }

    }

    #[Endpoint(operationId: 'updateCompanyProject', title: 'Atualizar projeto da empresa', description: '**operationId:** `updateCompanyProject` — Atualiza um projeto existente. Requer que o registro pertença ao perfil autenticado (`CompanyProjectPolicy::update`). Quando `title` ou `description` mudam, a tradução é refeita; quando `languages` é enviado, o vínculo de linguagens é sincronizado. Em **200**, `data` segue o schema **Company Project Resource** (`App\\Http\\Resources\\CompanyProject\\CompanyProjectResource`).')]
    public function update(UpdateCompanyProjectsRequest $request): JsonResponse {

        try {
            $companyProject = $this->companyProject->update($request->validated());

            return ApiResponse::success($companyProject, 'Project updated with success!', 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }

    }

    #[Endpoint(operationId: 'destroyCompanyProject', title: 'Remover projeto da empresa', description: '**operationId:** `destroyCompanyProject` — Remove um projeto. Requer que o registro pertença ao perfil autenticado (`CompanyProjectPolicy::delete`). Em **200**, `data` segue o schema **Company Project Resource** (`App\\Http\\Resources\\CompanyProject\\CompanyProjectResource`) com os dados do projeto removido.')]
    public function destroy(DestroyCompanyProjectRequest $request): JsonResponse {

        try {
            $companyProject = $this->companyProject->destroy($request->validated());

            return ApiResponse::success($companyProject, 'Project deleted with success!', 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }

    }

    #[Endpoint(operationId: 'indexCompanyProject', title: 'Listar projetos da empresa', description: '**operationId:** `indexCompanyProject` — Lista paginada dos projetos, com filtros opcionais por `company_profile_id` e `search` (busca em `title`, `description` e no nome das linguagens vinculadas). Em **200**, `data.data[]` segue o schema **Company Project Resource** (`App\\Http\\Resources\\CompanyProject\\CompanyProjectResource`) e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexCompanyProjectsRequest $request): JsonResponse {

        try {
            $companyProject = $this->companyProject->index($request->validated());

            return ApiResponse::success($companyProject, 'Projects indexed with success!', 200);
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
