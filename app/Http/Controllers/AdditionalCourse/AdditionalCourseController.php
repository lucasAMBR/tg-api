<?php

namespace App\Http\Controllers\AdditionalCourse;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdditionalCourse\DeleteAdditionalCourseRequest;
use App\Http\Requests\AdditionalCourse\IndexAdditionalCourseRequest;
use App\Http\Requests\AdditionalCourse\StoreAdditionalCourseRequest;
use App\Http\Requests\AdditionalCourse\UpdateAdditionalCourseRequest;
use App\Services\AdditionalCourse\AdditionalCourseService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class AdditionalCourseController extends Controller
{
    public function __construct(protected AdditionalCourseService $additionalCourseService){}

    #[Endpoint(operationId: 'indexAdditionalCourses', title: 'Listar cursos complementares', description: '**operationId:** `indexAdditionalCourses` — Lista paginada dos cursos complementares, com filtros opcionais por `dev_profile_id`, `search` (busca em `name` e `provider`) e `verified` (apenas cursos com certificado anexado). Em **200**, `data.data[]` segue o schema **Additional Course Resource** (`App\\Http\\Resources\\AdditionalCourse\\AdditionalCourseResource`) e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexAdditionalCourseRequest $request): JsonResponse
    {
        try {
            $additionalCourses = $this->additionalCourseService->index($request->validated());

            return ApiResponse::success($additionalCourses, 'Additional Courses Listed With Success!');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeAdditionalCourse', title: 'Cadastrar curso complementar', description: '**operationId:** `storeAdditionalCourse` — Cadastra um curso complementar para o perfil de desenvolvedor autenticado. O arquivo `certificate` é opcional e, quando enviado, é anexado à coleção de mídia `certificate`. Dispara a regeração do embedding do perfil. Em **201**, `data` segue o schema **Additional Course Resource** (`App\\Http\\Resources\\AdditionalCourse\\AdditionalCourseResource`).')]
    public function store(StoreAdditionalCourseRequest $request): JsonResponse
    {
        try {
            $additionalCourse = $this->additionalCourseService->store($request->validated());

            return ApiResponse::success($additionalCourse, 'Additional Course registered with success!', 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateAdditionalCourse', title: 'Atualizar curso complementar', description: '**operationId:** `updateAdditionalCourse` — Atualiza um curso complementar existente. Requer que o registro pertença ao perfil autenticado (`AdditionalCoursePolicy::update`). Quando `certificate` é enviado, um novo certificado é anexado. Dispara a regeração do embedding do perfil. Em **200**, `data` segue o schema **Additional Course Resource** (`App\\Http\\Resources\\AdditionalCourse\\AdditionalCourseResource`).')]
    public function update(UpdateAdditionalCourseRequest $request): JsonResponse
    {
        try {
            $additionalCourse = $this->additionalCourseService->update($request->validated());

            return ApiResponse::success($additionalCourse, 'Additional Course updated with success');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'deleteAdditionalCourse', title: 'Remover curso complementar', description: '**operationId:** `deleteAdditionalCourse` — Remove um curso complementar. Requer que o registro pertença ao perfil autenticado (`AdditionalCoursePolicy::delete`). Também dispara a regeração do embedding do perfil. Em **200**, `data` é `null`.')]
    public function delete(DeleteAdditionalCourseRequest $request): JsonResponse
    {
        try {
            $this->additionalCourseService->delete($request->validated());

            return ApiResponse::success(message: 'Additional Course removed with success!');
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
