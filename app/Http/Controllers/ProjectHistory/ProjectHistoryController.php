<?php

namespace App\Http\Controllers\ProjectHistory;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectHistory\DeleteProjectHistoryRequest;
use App\Http\Requests\ProjectHistory\IndexProjectHistoryRequest;
use App\Http\Requests\ProjectHistory\RemoveImageFromProjectRequest;
use App\Http\Requests\ProjectHistory\SaveImagesToProjectRequest;
use App\Http\Requests\ProjectHistory\ShowProjectHistoryRequest;
use App\Http\Requests\ProjectHistory\StoreProjectHistoryRequest;
use App\Http\Requests\ProjectHistory\UpdateProjectHistoryRequest;
use App\Services\ProjectHistory\ProjectHistoryService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class ProjectHistoryController extends Controller
{
    public function __construct(protected ProjectHistoryService $projectHistoryService){}

    #[Endpoint(operationId: 'indexProjectHistory', title: 'Listar projetos do desenvolvedor', description: '**operationId:** `indexProjectHistory` — Lista paginada dos projetos, com `languages` carregadas e filtros opcionais por `dev_profile_id` e `search` (busca em `title`, `description` e no nome das linguagens vinculadas). Em **200**, `data.data[]` segue o schema **Project History Resource** (`App\\Http\\Resources\\ProjectHistory\\ProjectHistoryResource`) e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexProjectHistoryRequest $request): JsonResponse
    {
        try {
            $projectHistories = $this->projectHistoryService->index($request->validated());

            return ApiResponse::success($projectHistories, "Project History indexed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showProjectHistory', title: 'Consultar projeto', description: '**operationId:** `showProjectHistory` — Retorna um projeto específico pelo identificador na rota. Em **200**, `data` segue o schema **Project History Resource** (`App\\Http\\Resources\\ProjectHistory\\ProjectHistoryResource`).')]
    public function show(ShowProjectHistoryRequest $request): JsonResponse {
        try {
            $projectHistoryItem = $this->projectHistoryService->show($request->validated());

            return ApiResponse::success($projectHistoryItem, "Project History Founded!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeProjectHistory', title: 'Cadastrar projeto', description: '**operationId:** `storeProjectHistory` — Cadastra um projeto para o perfil de desenvolvedor autenticado, vinculando as `languages` informadas. Dispara a tradução do conteúdo e a regeração do embedding do perfil. Em **201**, `data` segue o schema **Project History Resource** (`App\\Http\\Resources\\ProjectHistory\\ProjectHistoryResource`), já com as linguagens carregadas.')]
    public function store(StoreProjectHistoryRequest $request): JsonResponse
    {
        try {
            $projectHistory = $this->projectHistoryService->store($request->validated());

            return ApiResponse::success($projectHistory, "Project registered with success!", 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateProjectHistory', title: 'Atualizar projeto', description: '**operationId:** `updateProjectHistory` — Atualiza um projeto existente. Requer que o registro pertença ao perfil autenticado (`ProjectHistoryPolicy::update`). Quando `title` ou `description` mudam, a tradução é refeita; quando `languages` é enviado, o vínculo de linguagens é sincronizado. O embedding do perfil é regerado. Em **200**, `data` segue o schema **Project History Resource** (`App\\Http\\Resources\\ProjectHistory\\ProjectHistoryResource`).')]
    public function update(UpdateProjectHistoryRequest $request): JsonResponse
    {
        try {
            $projectHistory = $this->projectHistoryService->update($request->validated());

            return ApiResponse::success($projectHistory, "Project updated with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'deleteProjectHistory', title: 'Remover projeto', description: '**operationId:** `deleteProjectHistory` — Remove um projeto. Requer que o registro pertença ao perfil autenticado (`ProjectHistoryPolicy::delete`). Também dispara a regeração do embedding do perfil. Em **200**, `data` é `null`.')]
    public function delete(DeleteProjectHistoryRequest $request): JsonResponse
    {
        try {
            $this->projectHistoryService->delete($request->validated());

            return ApiResponse::success(message: "Project removed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeProjectHistoryImages', title: 'Adicionar imagens à galeria do projeto', description: '**operationId:** `storeProjectHistoryImages` — Anexa as imagens enviadas à coleção de mídia `gallery` do projeto. Requer que o registro pertença ao perfil autenticado (`ProjectHistoryPolicy::update`). Em **200**, `data` segue o schema **Project History Resource** (`App\\Http\\Resources\\ProjectHistory\\ProjectHistoryResource`).')]
    public function saveImagesInProject(SaveImagesToProjectRequest $request): JsonResponse
    {
        try {
            $projectHistory = $this->projectHistoryService->saveImagesInProject($request->validated());

            return ApiResponse::success($projectHistory, "Image saved with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'deleteProjectHistoryImage', title: 'Remover imagem da galeria do projeto', description: '**operationId:** `deleteProjectHistoryImage` — Remove a imagem informada da galeria do projeto. Requer que o registro pertença ao perfil autenticado (`ProjectHistoryPolicy::update`) e que a imagem pertença a esse projeto. Em **200**, `data` é `null`.')]
    public function removeImageFromProject(RemoveImageFromProjectRequest $request): JsonResponse
    {
        try {
            $this->projectHistoryService->removeImageFromProject($request->validated());

            return ApiResponse::success(message: "Image Removed with success!");
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
