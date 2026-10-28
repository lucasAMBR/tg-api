<?php

namespace App\Http\Controllers\LanguageAssessment;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DevLanguageAssessment\AnswerDevLanguageAssessmentRequest;
use App\Http\Requests\DevLanguageAssessment\ShowDevLanguageAssessmentRequest;
use App\Http\Requests\LanguageAssessment\DestroyLanguageAssessmentRequest;
use App\Http\Requests\LanguageAssessment\ShowLanguageAssessmentRequest;
use App\Http\Requests\LanguageAssessment\StoreLanguageAssessmentRequest;
use App\Http\Requests\LanguageAssessment\UpdateLanguageAssessmentRequest;
use App\Services\LanguageAssessment\LanguageAssessmentService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class LanguageAssessmentController extends Controller
{
    public function __construct(protected LanguageAssessmentService $languageAssessmentService) {}

    #[Endpoint(operationId: 'storeLanguageAssessment', title: 'Criar prova de idioma da vaga', description: '**operationId:** `storeLanguageAssessment` — Cria a prova de idioma da vaga informada, com as perguntas, as alternativas e o **gabarito** de cada uma (exige role `company` e que a vaga pertença ao perfil autenticado, caso contrário **403**). A vaga precisa ter a etapa `language_assessment` no processo seletivo e ainda não ter uma prova. Cada pergunta tem `type` `single_choice` / `multiple_choice` (respondidas nas alternativas, com o gabarito em `options[].is_correct`, mínimo de duas alternativas e nunca todas certas) ou `text` (respondida escrevendo, com o gabarito em `expected_answers`). O `weight` define quanto cada pergunta vale e o `passing_score` opcional é a nota de corte, que não pode passar da soma dos pesos. Diferente do questionário de triagem e do desafio técnico, o conteúdo **não é traduzido**: a prova é escrita no idioma avaliado. Se a vaga já estava em `awaiting_language_assessment`, o cadastro inicia a etapa. Em **201**, `data` segue o schema **Language Assessment Resource** (`App\\Http\\Resources\\LanguageAssessment\\LanguageAssessmentResource`), com o gabarito incluído.')]
    public function store(StoreLanguageAssessmentRequest $request): JsonResponse {

        try {
            $data = $this->languageAssessmentService->store($request->validated());

            return ApiResponse::success(
                $data,
                'Language assessment created with success!',
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

    #[Endpoint(operationId: 'showLanguageAssessment', title: 'Ver prova de idioma da vaga', description: '**operationId:** `showLanguageAssessment` — Retorna a prova de idioma da vaga informada, com as perguntas e as alternativas na ordem definida pela empresa, o **gabarito** completo e `dev_assessments_count` com quantos candidatos já a receberam (exige role `company` e que a vaga pertença ao perfil autenticado, caso contrário **403**). Vaga sem prova cadastrada responde **404**. Em **200**, `data` segue o schema **Language Assessment Resource** (`App\\Http\\Resources\\LanguageAssessment\\LanguageAssessmentResource`).')]
    public function show(ShowLanguageAssessmentRequest $request): JsonResponse {

        try {
            $data = $this->languageAssessmentService->showByJobVacancy($request->validated());

            return ApiResponse::success(
                $data,
                'Language assessment retrieved with success!',
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

    #[Endpoint(operationId: 'updateLanguageAssessment', title: 'Atualizar prova de idioma', description: '**operationId:** `updateLanguageAssessment` — Atualiza os dados da prova e as perguntas (exige role `company` e que a prova seja de uma vaga do perfil autenticado, caso contrário **403**). A lista de perguntas enviada substitui a atual: as que vierem com `id` são atualizadas, as sem `id` são criadas e as que ficarem de fora são removidas — o mesmo vale para as alternativas de cada pergunta, gabarito incluído. Só é aceito enquanto a etapa não começou, ou seja, enquanto a prova não foi enviada a nenhum candidato. Em **200**, `data` segue o schema **Language Assessment Resource** (`App\\Http\\Resources\\LanguageAssessment\\LanguageAssessmentResource`).')]
    public function update(UpdateLanguageAssessmentRequest $request): JsonResponse {

        try {
            $data = $this->languageAssessmentService->update($request->validated());

            return ApiResponse::success(
                $data,
                'Language assessment updated with success!',
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

    #[Endpoint(operationId: 'destroyLanguageAssessment', title: 'Remover prova de idioma', description: '**operationId:** `destroyLanguageAssessment` — Remove a prova da vaga, junto com as perguntas e as alternativas (exige role `company` e que a prova seja de uma vaga do perfil autenticado, caso contrário **403**). Só é aceito enquanto a prova não foi enviada a nenhum candidato. Em **200**, `data` vem `null`.')]
    public function destroy(DestroyLanguageAssessmentRequest $request): JsonResponse {

        try {
            $this->languageAssessmentService->destroy($request->validated());

            return ApiResponse::success(
                null,
                'Language assessment deleted with success!',
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

    #[Endpoint(operationId: 'showDevLanguageAssessment', title: 'Ver prova de idioma a responder', description: '**operationId:** `showDevLanguageAssessment` — Retorna a prova do desenvolvedor autenticado, com as perguntas e as alternativas (exige role `dev`; provas de outro desenvolvedor respondem **403**). **O gabarito não vem antes da entrega**: `options[].is_correct` e `expected_answers` só aparecem depois que a prova é respondida. Depois da entrega vêm também as respostas enviadas, a correção de cada uma e a nota. O `id` é o da prova do candidato, que chega no campo `language_assessment` da candidatura. Em **200**, `data` segue o schema **Dev Language Assessment Resource** (`App\\Http\\Resources\\DevLanguageAssessment\\DevLanguageAssessmentResource`).')]
    public function showDevAssessment(ShowDevLanguageAssessmentRequest $request): JsonResponse {

        try {
            $data = $this->languageAssessmentService->showDevAssessment($request->validated());

            return ApiResponse::success(
                $data,
                'Language assessment retrieved with success!',
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

    #[Endpoint(operationId: 'answerDevLanguageAssessment', title: 'Responder prova de idioma', description: '**operationId:** `answerDevLanguageAssessment` — Entrega a prova do desenvolvedor autenticado (exige role `dev`; provas de outro desenvolvedor respondem **403**). Só é aceito uma vez, com a candidatura em andamento e parada na etapa `language_assessment`. Cada item de `answers` traz o `question_id` e, conforme o tipo da pergunta, `response` (escrita) ou `option_ids` (uma alternativa na escolha única, uma ou mais na múltipla escolha). Perguntas deixadas em branco são aceitas e valem zero. A **correção é automática e imediata**: nas de escolha o conjunto marcado precisa bater exatamente com o gabarito (sem acerto parcial) e nas escritas o texto é comparado com as respostas aceitas, ignorando caixa e espaço repetido mas mantendo acento. A prova passa para `answered` com `score`, `max_score` e `passed` (contra o `passing_score`, ou `null` quando não há nota de corte), e a empresa é notificada. Em **200**, `data` segue o schema **Dev Language Assessment Resource** (`App\\Http\\Resources\\DevLanguageAssessment\\DevLanguageAssessmentResource`), já com o gabarito.')]
    public function answer(AnswerDevLanguageAssessmentRequest $request): JsonResponse {

        try {
            $data = $this->languageAssessmentService->answer($request->validated());

            return ApiResponse::success(
                $data,
                'Language assessment answered with success!',
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
