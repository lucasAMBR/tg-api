<?php

namespace App\Http\Controllers\DevJobVacancy;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DevJobVacancy\AdvanceStepDevJobVacancyRequest;
use App\Http\Requests\DevJobVacancy\IndexDevJobVacancyRequest;
use App\Http\Requests\DevJobVacancy\IndexMyAppliesDevJobVacancyRequest;
use App\Http\Requests\DevJobVacancy\ReviewDevJobVacancyRequest;
use App\Http\Requests\DevJobVacancy\IndexStepAppliesDevJobVacancyRequest;
use App\Http\Requests\DevJobVacancy\StepResultsDevJobVacancyRequest;
use App\Http\Requests\DevJobVacancy\StoreDevJobVacancyRequest;
use App\Services\DevJobVacancy\DevJobVacancyService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class DevJobVacancyController extends Controller
{
    public function __construct(protected DevJobVacancyService $devJobVacancyService) {}

    #[Endpoint(operationId: 'storeDevJobVacancy', title: 'Candidatar-se a uma vaga', description: '**operationId:** `storeDevJobVacancy` — Inscreve o desenvolvedor autenticado na vaga informada, com status inicial `in_progress` e `process_step` igual ao passo atual da vaga. Exige role `dev` e perfil de desenvolvedor existente, recusa candidaturas duplicadas para a mesma vaga e vagas que não estejam com `open_inscriptions`. Em **201**, `data` segue o schema **Dev Job Vacancy Resource** (`App\\Http\\Resources\\DevJobVacancy\\DevJobVacancyResource`), com `jobVacancy` e `devProfile` carregados.')]
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

    #[Endpoint(operationId: 'indexApplies', title: 'Listar candidaturas das vagas da empresa', description: '**operationId:** `indexApplies` — Lista paginada das candidaturas às vagas do perfil de empresa autenticado (exige role `company`), com filtros opcionais `search` (busca no nome do desenvolvedor e no título da vaga), `status` (`in_progress`, `approved`, `rejected`) e `job_vacancy_id` (restringe às candidaturas de uma vaga específica). Em **200**, `data.data[]` segue o schema **Dev Job Vacancy Resource** (`App\\Http\\Resources\\DevJobVacancy\\DevJobVacancyResource`) e `data.pagination` traz os metadados de paginação.')]
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

    #[Endpoint(operationId: 'indexMyApplies', title: 'Listar minhas candidaturas', description: '**operationId:** `indexMyApplies` — Lista paginada das candidaturas do desenvolvedor autenticado (exige role `dev`), com filtros opcionais `search` (busca no título da vaga e no nome da empresa) e `status` (`in_progress`, `approved`, `rejected`). Em **200**, `data.data[]` segue o schema **Dev Job Vacancy Resource** (`App\\Http\\Resources\\DevJobVacancy\\DevJobVacancyResource`) e `data.pagination` traz os metadados de paginação.')]
    public function indexMyApplies(IndexMyAppliesDevJobVacancyRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyService->indexMyApplies($request->validated());

            return ApiResponse::success(
                $data,
                'My applies indexed with success!',
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

    #[Endpoint(operationId: 'indexStepApplies', title: 'Listar candidaturas em andamento de um passo', description: '**operationId:** `indexStepApplies` — Lista completa, sem paginação, das candidaturas com status `in_progress` que estão paradas em um passo do processo seletivo da vaga informada (exige role `company` e que a vaga pertença ao perfil autenticado, caso contrário **403**). O passo é definido por `process_step` e, quando omitido, assume `resume_screening`. Aceita ainda o filtro opcional `search` (busca no nome do desenvolvedor). Em **200**, `data[]` segue o schema **Dev Job Vacancy Resource** (`App\\Http\\Resources\\DevJobVacancy\\DevJobVacancyResource`); na etapa `portfolio_review` cada item traz também `portfolio_solicitation`, com a url, o tipo, o prazo e o status (`pending`/`sent`) do envio do portfólio.')]
    public function indexStepApplies(IndexStepAppliesDevJobVacancyRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyService->indexStepApplies($request->validated());

            return ApiResponse::success(
                $data,
                'Step applies indexed with success!',
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

    #[Endpoint(operationId: 'stepResults', title: 'Resultados de um passo do processo seletivo', description: '**operationId:** `stepResults` — Retorna dois objetos para a vaga informada (exige role `company` e que a vaga pertença ao perfil autenticado, caso contrário **403**): `approved`, com as candidaturas aprovadas no passo informado — ou seja, as que já estão em um passo posterior do processo seletivo da vaga e as que o concluíram com status `approved` —, e `rejected_in_step`, com todas as candidaturas recusadas no passo informado. O passo é definido por `process_step` e, quando omitido, assume `resume_screening`. Em **200**, `data.approved[]` e `data.rejected_in_step[]` seguem o schema **Dev Job Vacancy Resource** (`App\\Http\\Resources\\DevJobVacancy\\DevJobVacancyResource`), com `jobVacancy` e `devProfile` carregados.')]
    public function stepResults(StepResultsDevJobVacancyRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyService->stepResults($request->validated());

            return ApiResponse::success(
                $data,
                'Step results retrieved with success!',
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

    #[Endpoint(operationId: 'advanceStep', title: 'Avançar candidaturas para a próxima etapa', description: '**operationId:** `advanceStep` — Encerra a etapa atual do processo seletivo da vaga (exige role `company`, que a vaga pertença ao perfil autenticado — caso contrário **403** — e que as inscrições já estejam encerradas). As candidaturas informadas em `apply_ids` são aprovadas na etapa e avançam para a próxima etapa configurada na vaga; todas as demais candidaturas `in_progress` da etapa atual são recusadas, mantendo o `process_step` em que a recusa aconteceu. Quando a etapa atual é a última, as aprovadas recebem o status `approved`. A lista pode vir vazia, o que recusa todas as candidaturas da etapa. Todos os desenvolvedores são notificados do resultado da etapa e, quando a próxima etapa é a análise de portfólio (`portfolio_review`), cada aprovado recebe uma solicitação de portfólio com prazo em `due_date` (opcional, padrão de 7 dias). Em **200**, `data.process_step` traz a nova etapa (ou `null` no fim do processo) e `data.approved[]` e `data.rejected[]` seguem o schema **Dev Job Vacancy Resource** (`App\\Http\\Resources\\DevJobVacancy\\DevJobVacancyResource`).')]
    public function advanceStep(AdvanceStepDevJobVacancyRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyService->advanceStep($request->validated());

            return ApiResponse::success(
                $data,
                'Applies advanced to the next step with success!',
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

    #[Endpoint(operationId: 'reviewApply', title: 'Avaliar candidatura', description: '**operationId:** `reviewApply` — Atualiza o `status` e/ou o `feedback` de uma candidatura, ambos opcionais. A atualização só é aplicada quando a vaga pertence ao perfil de empresa autenticado; caso contrário a resposta é **403**. Em **200**, `data` segue o schema **Dev Job Vacancy Resource** (`App\\Http\\Resources\\DevJobVacancy\\DevJobVacancyResource`), com `jobVacancy` e `devProfile` carregados.')]
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
