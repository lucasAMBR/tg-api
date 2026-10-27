<?php

namespace App\Http\Controllers\FreelanceJobVacancy;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\FreelanceJobVacancy\CompleteFreelanceJobVacancyRequest;
use App\Http\Requests\FreelanceJobVacancy\DestroyFreelanceJobVacancyRequest;
use App\Http\Requests\FreelanceJobVacancy\IndexFreelanceJobVacancyRequest;
use App\Http\Requests\FreelanceJobVacancy\IndexMyFreelanceContractsRequest;
use App\Http\Requests\FreelanceJobVacancy\IndexMyFreelanceJobVacanciesRequest;
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

    #[Endpoint(operationId: 'indexFreelanceJobVacancy', title: 'Listar vagas freelance', description: '**operationId:** `indexFreelanceJobVacancy` — Vitrine paginada dos freelas **abertos** (que ainda recebem orçamento), do mais recente para o mais antigo, com `languages` e o perfil público do cliente. Aceita os filtros opcionais `search` (busca em `title`, `job_type`, `seniority_level` e nos nomes das linguagens vinculadas), `job_type`, `seniority_level` e `invited` (só para dev: restringe aos freelas para os quais ele foi convidado). Para o dev, cada vaga traz `my_proposal` e `is_invited`. Em **200**, `data.data[]` segue o schema **Freelance Job Vacancy Resource** (`App\\Http\\Resources\\FreelanceJobVacancy\\FreelanceJobVacancyResource`) e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexFreelanceJobVacancyRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceJobVacancy->index($request->validated());

            return ApiResponse::success(
                $data,
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

    #[Endpoint(operationId: 'indexMyFreelanceJobVacancies', title: 'Listar meus freelas publicados', description: '**operationId:** `indexMyFreelanceJobVacancies` — Lista paginada dos freelas publicados pelo cliente autenticado (exige role `client`), em qualquer status, do mais recente para o mais antigo. Cada vaga traz `status`, `budget`, `proposals_count` e, depois do contrato fechado, o `contract` (o orçamento escolhido, com o dev). Aceita os filtros opcionais `status` (`open`, `in_progress`, `completed`) e `search`. Em **200**, `data.data[]` segue o schema **Freelance Job Vacancy Resource** (`App\\Http\\Resources\\FreelanceJobVacancy\\FreelanceJobVacancyResource`) e `data.pagination` traz os metadados de paginação.')]
    public function indexMyVacancies(IndexMyFreelanceJobVacanciesRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceJobVacancy->indexMyVacancies($request->validated());

            return ApiResponse::success(
                $data,
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

    #[Endpoint(operationId: 'indexMyFreelanceContracts', title: 'Listar meus freelas contratados', description: '**operationId:** `indexMyFreelanceContracts` — Lista paginada dos freelas em que o dev autenticado foi contratado (exige role `dev`): os em andamento primeiro, depois os concluídos, cada grupo do contrato mais recente para o mais antigo. Cada vaga traz o `contract` com o orçamento aceito. Aceita os filtros opcionais `status` (`in_progress` ou `completed`) e `search`. Em **200**, `data.data[]` segue o schema **Freelance Job Vacancy Resource** (`App\\Http\\Resources\\FreelanceJobVacancy\\FreelanceJobVacancyResource`) e `data.pagination` traz os metadados de paginação.')]
    public function indexMyContracts(IndexMyFreelanceContractsRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceJobVacancy->indexMyContracts($request->validated());

            return ApiResponse::success(
                $data,
                'Freelance contracts indexed with success!',
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

    #[Endpoint(operationId: 'storeFreelanceJobVacancy', title: 'Cadastrar vaga freelance', description: '**operationId:** `storeFreelanceJobVacancy` — Cadastra uma vaga freelance aberta para o perfil de cliente autenticado (exige role `client` e perfil criado), vinculando as linguagens com o respectivo nível. O `budget` é quanto o cliente pretende pagar pelo serviço inteiro: só ele vê, e é a referência de preço do ranking de orçamentos. O campo `languages` só é obrigatório quando o `job_type` escolhido depende de uma stack (desenvolvimento, manutenção, QA, devops, banco de dados, automação, integração e migração); nos demais tipos ele é opcional. Em **201**, `data` segue o schema **Freelance Job Vacancy Resource** (`App\\Http\\Resources\\FreelanceJobVacancy\\FreelanceJobVacancyResource`), com as relações carregadas.')]
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

    #[Endpoint(operationId: 'showFreelanceJobVacancy', title: 'Consultar vaga freelance', description: '**operationId:** `showFreelanceJobVacancy` — Retorna uma vaga freelance específica, em qualquer status, com `languages` e o perfil público do cliente. O cliente dono vê também `budget`, `proposals_count` e `contract`; o dev vê `my_proposal` e `is_invited`, e o `contract` quando é o contratado. Em **200**, `data` segue o schema **Freelance Job Vacancy Resource** (`App\\Http\\Resources\\FreelanceJobVacancy\\FreelanceJobVacancyResource`).')]
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

    #[Endpoint(operationId: 'updateFreelanceJobVacancy', title: 'Atualizar vaga freelance', description: '**operationId:** `updateFreelanceJobVacancy` — Atualiza os dados da vaga freelance do cliente autenticado, enquanto ela está aberta (depois do contrato fechado, o escopo não muda mais). Quando `languages` é enviado, o array substitui por completo os vínculos atuais. A validação recusa o payload que deixaria a vaga sem stack em um `job_type` que a exige. Em **200**, `data` segue o schema **Freelance Job Vacancy Resource** (`App\\Http\\Resources\\FreelanceJobVacancy\\FreelanceJobVacancyResource`).')]
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

    #[Endpoint(operationId: 'deleteFreelanceJobVacancy', title: 'Remover vaga freelance', description: '**operationId:** `deleteFreelanceJobVacancy` — Remove a vaga freelance do cliente autenticado e desfaz os vínculos com as linguagens. Só vale para a vaga aberta: depois do contrato fechado, ela guarda o histórico do trabalho no feed. Em **200**, `data` é `null`.')]
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

    #[Endpoint(operationId: 'completeFreelanceJobVacancy', title: 'Concluir freela', description: '**operationId:** `completeFreelanceJobVacancy` — O cliente dono marca como concluído o freela em andamento (`in_progress` → `completed`) e o dev contratado é notificado. O feed continua disponível, só para consulta. Em **200**, `data` segue o schema **Freelance Job Vacancy Resource** (`App\\Http\\Resources\\FreelanceJobVacancy\\FreelanceJobVacancyResource`).')]
    public function complete(CompleteFreelanceJobVacancyRequest $request): JsonResponse
    {
        try {
            $data = $this->freelanceJobVacancy->complete($request->validated());

            return ApiResponse::success(
                new FreelanceJobVacancyResource($data),
                'Freelance job vacancy completed with success!',
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
