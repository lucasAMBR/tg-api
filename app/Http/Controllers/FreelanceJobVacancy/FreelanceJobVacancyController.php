<?php

namespace App\Http\Controllers\FreelanceJobVacancy;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FreelanceJobVacancy\DestroyFreelanceJobVacancyRequest;
use App\Http\Requests\FreelanceJobVacancy\IndexFreelanceJobVacancyRequest;
use App\Http\Requests\FreelanceJobVacancy\ShowFreelanceJobVacancyRequest;
use App\Http\Requests\FreelanceJobVacancy\StoreFreelanceJobVacancyRequest;
use App\Http\Requests\FreelanceJobVacancy\UpdateFreelanceJobVacancyRequest;
use App\Http\Resources\FreelanceJobVacancy\FreelanceJobVacancyResource;
use App\Services\FreelanceJobVacancy\FreelanceJobVacancyService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class FreelanceJobVacancyController extends Controller
{
    public function __construct(protected FreelanceJobVacancyService $freelanceJobVacancy) {}

    #[Endpoint(operationId: 'indexFreelanceJobVacancy', title: 'Listar vagas freelance', description: '**operationId:** `indexFreelanceJobVacancy` — Lista paginada das vagas freelance publicadas por clientes, com `languages` e `clientProfile` carregados. Aceita os filtros opcionais `search` (busca em `title`, `job_type`, `seniority_level` e nos nomes das linguagens vinculadas), `job_type`, `seniority_level` e `only_mine` (restringe às vagas do cliente autenticado). Em **200**, `data[]` segue o schema **Freelance Job Vacancy Resource** (`App\\Http\\Resources\\FreelanceJobVacancy\\FreelanceJobVacancyResource`).')]
    public function index(IndexFreelanceJobVacancyRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceJobVacancy->index($request->validated());

            return ApiResponse::success(
                FreelanceJobVacancyResource::collection($data),
                'Freelance job vacancies indexed with success!',
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

    #[Endpoint(operationId: 'storeFreelanceJobVacancy', title: 'Cadastrar vaga freelance', description: '**operationId:** `storeFreelanceJobVacancy` — Cadastra uma vaga freelance para o perfil de cliente autenticado (exige role `client` e perfil ativo), vinculando as linguagens com o respectivo nível. O campo `languages` só é obrigatório quando o `job_type` escolhido depende de uma stack (desenvolvimento, manutenção, QA, devops, banco de dados, automação, integração e migração); nos demais tipos ele é opcional. Em **201**, `data` segue o schema **Freelance Job Vacancy Resource** (`App\\Http\\Resources\\FreelanceJobVacancy\\FreelanceJobVacancyResource`), com as relações carregadas.')]
    public function store(StoreFreelanceJobVacancyRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceJobVacancy->store($request->validated());

            return ApiResponse::success(
                new FreelanceJobVacancyResource($data),
                'Freelance job vacancy created with success!',
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

    #[Endpoint(operationId: 'showFreelanceJobVacancy', title: 'Consultar vaga freelance', description: '**operationId:** `showFreelanceJobVacancy` — Retorna uma vaga freelance específica pelo identificador na rota, com `languages` e `clientProfile` carregados. Em **200**, `data` segue o schema **Freelance Job Vacancy Resource** (`App\\Http\\Resources\\FreelanceJobVacancy\\FreelanceJobVacancyResource`).')]
    public function show(ShowFreelanceJobVacancyRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceJobVacancy->show($request->validated());

            return ApiResponse::success(
                new FreelanceJobVacancyResource($data),
                'Freelance job vacancy indexed with success!',
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

    #[Endpoint(operationId: 'updateFreelanceJobVacancy', title: 'Atualizar vaga freelance', description: '**operationId:** `updateFreelanceJobVacancy` — Atualiza os dados da vaga freelance do cliente autenticado. Quando `languages` é enviado, o array substitui por completo os vínculos atuais. A validação recusa o payload que deixaria a vaga sem stack em um `job_type` que a exige. Em **200**, `data` segue o schema **Freelance Job Vacancy Resource** (`App\\Http\\Resources\\FreelanceJobVacancy\\FreelanceJobVacancyResource`).')]
    public function update(UpdateFreelanceJobVacancyRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceJobVacancy->update($request->validated());

            return ApiResponse::success(
                new FreelanceJobVacancyResource($data),
                'Freelance job vacancy updated with success!',
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

    #[Endpoint(operationId: 'deleteFreelanceJobVacancy', title: 'Remover vaga freelance', description: '**operationId:** `deleteFreelanceJobVacancy` — Remove a vaga freelance do cliente autenticado e desfaz os vínculos com as linguagens. Em **200**, `data` é `null`.')]
    public function destroy(DestroyFreelanceJobVacancyRequest $request): JsonResponse
    {
        try {
            $this->freelanceJobVacancy->destroy($request->validated());

            return ApiResponse::success(
                null,
                'Freelance job vacancy deleted with success!',
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
