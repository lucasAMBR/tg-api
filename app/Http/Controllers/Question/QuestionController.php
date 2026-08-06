<?php

namespace App\Http\Controllers\Question;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Question\AddResponseToQuestionRequest;
use App\Http\Requests\Question\DeleteQuestionRequest;
use App\Http\Requests\Question\DeleteQuestionResponseRequest;
use App\Http\Requests\Question\IndexQuestionRequest;
use App\Http\Requests\Question\ShowQuestionRequest;
use App\Http\Requests\Question\StoreQuestionRequest;
use App\Http\Requests\Question\UpdateQuestionRequest;
use App\Http\Requests\Question\UpdateQuestionResponseRequest;
use App\Services\Question\QuestionService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class QuestionController extends Controller
{
    public function __construct(protected QuestionService $questionService){}

    #[Endpoint(operationId: 'indexQuestions', title: 'Listar questões', description: '**operationId:** `indexQuestions` — Lista paginada das questões, com `question_responses` carregadas e filtros opcionais por `search` (enunciado, traduções, alternativas e nome da linguagem), `difficulty_level`, `language_id`, `category`, `seniority_level`, `is_multiple_choice` e `translation_status`. Em **200**, `data.data[]` segue o schema **Question Resource** (`App\\Http\\Resources\\Question\\QuestionResource`) e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexQuestionRequest $request): JsonResponse
    {
        try {
            $questions = $this->questionService->indexQuestions($request->validated());

            return ApiResponse::success($questions, "Questions fetched successfully");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showQuestion', title: 'Consultar questão', description: '**operationId:** `showQuestion` — Retorna uma questão específica pelo identificador na rota, com `question_responses` e `language` carregados. Em **200**, `data` segue o schema **Question Resource** (`App\\Http\\Resources\\Question\\QuestionResource`).')]
    public function show(ShowQuestionRequest $request): JsonResponse
    {
        try {
            $question = $this->questionService->showQuestion($request->validated());

            return ApiResponse::success($question, "Question fetched successfully");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeQuestion', title: 'Cadastrar questão', description: '**operationId:** `storeQuestion` — Cadastra uma questão e, quando enviadas, também as alternativas em `responses`. A tradução do enunciado e de cada alternativa é disparada em background. Em **200**, `data` segue o schema **Question Resource** (`App\\Http\\Resources\\Question\\QuestionResource`), com as alternativas carregadas.')]
    public function store(StoreQuestionRequest $request): JsonResponse
    {
        try {
            $question = $this->questionService->storeQuestion($request->validated());

            return ApiResponse::success($question, "Question created successfully");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateQuestion', title: 'Atualizar questão', description: '**operationId:** `updateQuestion` — Atualiza os dados da questão. Com `manual_update_translation`, as traduções `question_pt` e `question_en` enviadas são gravadas diretamente; caso contrário a tradução automática é disparada. Em **200**, `data` segue o schema **Question Resource** (`App\\Http\\Resources\\Question\\QuestionResource`).')]
    public function update(UpdateQuestionRequest $request): JsonResponse
    {
        try {
            $question = $this->questionService->updateQuestion($request->validated());

            return ApiResponse::success($question, "Question updated successfully");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'deleteQuestion', title: 'Remover questão', description: '**operationId:** `deleteQuestion` — Remove a questão informada. Em **200**, `data` é `null`.')]
    public function deleteQuestion(DeleteQuestionRequest $request): JsonResponse
    {
        try {
            $this->questionService->deleteQuestion($request->validated());

            return ApiResponse::success(null, "Question deleted successfully");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'addResponseToQuestion', title: 'Adicionar alternativa à questão', description: '**operationId:** `addResponseToQuestion` — Adiciona uma alternativa à questão e dispara a tradução do conteúdo. A questão aceita no máximo 4 alternativas e precisa ter ao menos uma correta — a quarta alternativa não pode ser incorreta se nenhuma das anteriores for correta. Em **200**, `data` segue o schema **Question Resource** (`App\\Http\\Resources\\Question\\QuestionResource`), com as alternativas carregadas.')]
    public function addResponse(AddResponseToQuestionRequest $request): JsonResponse
    {
        try {
            $response = $this->questionService->addResponseToQuestion($request->validated());

            return ApiResponse::success($response, "Response added successfully");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'deleteQuestionResponse', title: 'Remover alternativa', description: '**operationId:** `deleteQuestionResponse` — Remove a alternativa informada. Em **200**, `data` é `null`.')]
    public function deleteResponse(DeleteQuestionResponseRequest $request): JsonResponse
    {
        try {
            $this->questionService->deleteResponseFromQuestion($request->validated());

            return ApiResponse::success(null, "Response removed successfully");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateQuestionResponse', title: 'Atualizar alternativa', description: '**operationId:** `updateQuestionResponse` — Atualiza a alternativa informada. Com `manual_update_translation`, as traduções `response_pt` e `response_en` enviadas são gravadas diretamente; caso contrário a tradução automática é disparada. Em **200**, `data` segue o schema **Question Response Resource** (`App\\Http\\Resources\\Question\\QuestionResponseResource`).')]
    public function updateResponse(UpdateQuestionResponseRequest $request): JsonResponse
    {
        try {
            $updatedResponse = $this->questionService->updateResponse($request->validated());

            return ApiResponse::success($updatedResponse, "Response updated successfully");
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
