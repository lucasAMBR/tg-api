<?php

namespace App\Http\Controllers\ProficiencyTest;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProficiencyTest\GetProficiencyTestQuestionsRequest;
use App\Http\Requests\ProficiencyTest\GetProficiencyTestReviewRequest;
use App\Http\Requests\ProficiencyTest\IndexProficiencyTest;
use App\Http\Requests\ProficiencyTest\ShowProficiencyTestRequest;
use App\Http\Requests\ProficiencyTest\SolicitateProficiencyTestRequest;
use App\Http\Requests\ProficiencyTest\StoreProficiencyTestVisualizationRequest;
use App\Http\Requests\ProficiencyTest\SubmitProficiencyTestRequest;
use App\Services\ProficiencyTest\ProficiencyTestService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class ProficiencyTestController extends Controller
{
    public function __construct(private ProficiencyTestService $proficiencyTestService){}

    #[Endpoint(operationId: 'solicitateProficiencyTest', title: 'Solicitar teste de proficiência', description: '**operationId:** `solicitateProficiencyTest` — Cria um teste de proficiência com status `PENDING` para o perfil de desenvolvedor informado e dispara a geração das questões em background. É recusado quando o último teste ainda tem respostas pendentes ou quando já houve solicitação no último mês (**422**). Em **200**, `data` segue o schema **Proficiency Test Resource** (`App\\Http\\Resources\\ProficiencyTest\\ProficiencyTestResource`).')]
    public function solicitateProficiencyTest(SolicitateProficiencyTestRequest $request): JsonResponse
    {
        try {
            $ProficiencyTest = $this->proficiencyTestService->solicitateProficiencyTest($request->validated());

            return ApiResponse::success($ProficiencyTest, 'Proficiency test solicitated with success', 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'listTestQuestions', title: 'Consultar questões do teste', description: '**operationId:** `listTestQuestions` — Retorna as questões do teste agrupadas em páginas, conforme `app.proficiency_test.questions_per_page`. Em **200**, `data` é uma lista de páginas, cada uma com itens no schema **Question Resource** (`App\\Http\\Resources\\Question\\QuestionResource`).')]
    public function getProficiencyTestQuestions(GetProficiencyTestQuestionsRequest $request): JsonResponse
    {
        try {
            $questions = $this->proficiencyTestService->getProficiencyTestQuestions($request->validated());

            /**
             * @status 200
             *
             * @body array{error: false, message: string, data: array<int, array<int, \App\Http\Resources\Question\QuestionResource>>}
             */
            return ApiResponse::success($questions, 'Proficiency test questions retrieved with success', 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'submitProficiencyTest', title: 'Enviar respostas do teste', description: '**operationId:** `submitProficiencyTest` — Registra as respostas enviadas em `chunks` (rateando o `time_taken` de cada página entre as questões pelo tempo ideal de resolução), move o teste para `AWAITING_SCORE`, notifica o desenvolvedor e dispara o cálculo da pontuação em background. Testes já concluídos são recusados (**422**). Em **200**, `data` segue o schema **Proficiency Test Resource** (`App\\Http\\Resources\\ProficiencyTest\\ProficiencyTestResource`).')]
    public function submitProficiencyTest(SubmitProficiencyTestRequest $request): JsonResponse
    {
        try {
            $result = $this->proficiencyTestService->submitProficiencyTest($request->validated());

            return ApiResponse::success($result, 'Proficiency test submitted with success', 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'indexProficiencyTest', title: 'Listar testes de proficiência', description: '**operationId:** `indexProficiencyTest` — Lista paginada dos testes de proficiência, ordenada por `solicitation_date` decrescente, com filtros opcionais por `search` (nome do desenvolvedor), `dev_profile_id`, `seniority_level`, `specialty` e `status`. Em **200**, `data.data[]` segue o schema **Proficiency Test Resource** (`App\\Http\\Resources\\ProficiencyTest\\ProficiencyTestResource`) e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexProficiencyTest $request): JsonResponse
    {
        try {
            $proficiencyTests = $this->proficiencyTestService->index($request->validated());

            return ApiResponse::success($proficiencyTests, 'Proficiency tests listed with success', 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'visualizeTest', title: 'Registrar visualização do teste', description: '**operationId:** `visualizeTest` — Registra uma visualização do teste com o `type` informado. Em **201**, `data` traz o registro de visualização criado (`proficiency_test_id` e `type`).')]
    public function registerVisualization(StoreProficiencyTestVisualizationRequest $request): JsonResponse
    {
        try {
            $visualization = $this->proficiencyTestService->registerVisualization($request->validated());

            return ApiResponse::success($visualization, 'Proficiency test visualization registered with success', 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'testReview', title: 'Consultar revisão do teste', description: '**operationId:** `testReview` — Retorna a revisão do teste: cada questão respondida com suas alternativas e a resposta escolhida pelo desenvolvedor. Em **200**, `data[]` segue o schema **Proficiency Test Review Resource** (`App\\Http\\Resources\\ProficiencyTest\\ProficiencyTestReviewResource`).')]
    public function getProficiencyTestReview(GetProficiencyTestReviewRequest $request): JsonResponse
    {
        try {
            $review = $this->proficiencyTestService->getProficiencyTestReview($request->validated());

            return ApiResponse::success($review, 'Proficiency test review retrieved with success', 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showProficiencyTest', title: 'Consultar teste de proficiência', description: '**operationId:** `showProficiencyTest` — Retorna um teste de proficiência específico pelo identificador na rota. Em **200**, `data` segue o schema **Proficiency Test Resource** (`App\\Http\\Resources\\ProficiencyTest\\ProficiencyTestResource`).')]
    public function show(ShowProficiencyTestRequest $request): JsonResponse
    {
        try {
            $proficiencyTest = $this->proficiencyTestService->show($request->validated());

            return ApiResponse::success($proficiencyTest, 'Proficiency test retrieved with success', 200);
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
