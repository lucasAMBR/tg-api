<?php

namespace App\Http\Controllers\JobVacancy;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\JobVacancy\DestroyJobVacancyRequest;
use App\Http\Requests\JobVacancy\IndexJobVacancyRequest;
use App\Http\Requests\JobVacancy\ShowJobVacancyRequest;
use App\Http\Requests\JobVacancy\StoreJobVacancyRequest;
use App\Http\Requests\JobVacancy\UpdateJobVacancyRequest;
use App\Http\Resources\JobVacancy\JobVacancyResource;
use App\Services\JobVacancy\JobVacancyService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class JobVacancyController extends Controller
{

    public function __construct(protected JobVacancyService $jobVacancy) {}

    #[Endpoint(operationId: 'indexJobVacancy', title: 'Listar vagas', description: '**operationId:** `indexJobVacancy` — Lista paginada das vagas, com `softSkill` e `languages` carregados e filtro opcional `search` (busca em `title`, `contract_type`, `seniority_level` e nos nomes das linguagens e soft skills vinculadas). Em **200**, `data[]` segue o schema **Job Vacancy Resource** (`App\\Http\\Resources\\JobVacancy\\JobVacancyResource`).')]
    public function index(IndexJobVacancyRequest $request): JsonResponse {

        try {
            $data = $this->jobVacancy->index($request->validated());

            return ApiResponse::success(
                JobVacancyResource::collection($data),
                'Job vacancies indexed with success!',
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

    #[Endpoint(operationId: 'storeJobVacancy', title: 'Cadastrar vaga', description: '**operationId:** `storeJobVacancy` — Cadastra uma vaga para o perfil de empresa autenticado (exige role `company` e perfil ativo), vinculando linguagens com nível, soft skills, linguagens desejáveis e as etapas do processo seletivo. Dispara a geração do embedding da vaga e a tradução do conteúdo. Em **201**, `data` segue o schema **Job Vacancy Resource** (`App\\Http\\Resources\\JobVacancy\\JobVacancyResource`), com as relações carregadas.')]
    public function store(StoreJobVacancyRequest $request): JsonResponse {

        try {
            $data = $this->jobVacancy->store($request->validated());

            return ApiResponse::success(
                new JobVacancyResource($data),
                'Job vacancy created with success!',
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

    #[Endpoint(operationId: 'showJobVacancy', title: 'Consultar vaga', description: '**operationId:** `showJobVacancy` — Retorna uma vaga específica pelo identificador na rota, com `softSkill` e `languages` carregados. Em **200**, `data` segue o schema **Job Vacancy Resource** (`App\\Http\\Resources\\JobVacancy\\JobVacancyResource`).')]
    public function show(ShowJobVacancyRequest $request): JsonResponse {

        try {
            $data = $this->jobVacancy->show($request->validated());

            return ApiResponse::success(
                new JobVacancyResource($data),
                'Job vacancy indexed with success!',
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

    #[Endpoint(operationId: 'updateJobVacancy', title: 'Atualizar vaga', description: '**operationId:** `updateJobVacancy` — Atualiza os dados da vaga e, quando enviados, os vínculos de linguagens (troca da linguagem e/ou do `language_level`) e de soft skills. Dispara a regeração do embedding da vaga e, quando `title`, `description` ou `benefits` mudam, a tradução do conteúdo. Em **200**, `data` segue o schema **Job Vacancy Resource** (`App\\Http\\Resources\\JobVacancy\\JobVacancyResource`).')]
    public function update(UpdateJobVacancyRequest $request): JsonResponse {

        try {
            $data = $this->jobVacancy->update($request->validated());

            return ApiResponse::success(
                new JobVacancyResource($data),
                'Job vacancy updated with success!',
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

    #[Endpoint(operationId: 'destroyJobVacancy', title: 'Remover vaga', description: '**operationId:** `destroyJobVacancy` — Remove a vaga e desfaz os vínculos com linguagens e soft skills. Em **200**, `data` é `null`.')]
    public function destroy(DestroyJobVacancyRequest $request): JsonResponse {

        try {
            $this->jobVacancy->destroy($request->validated());

            return ApiResponse::success(
                null,
                'Job vacancy deleted with success!',
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
