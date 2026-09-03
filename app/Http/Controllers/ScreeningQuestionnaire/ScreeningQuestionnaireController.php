<?php

namespace App\Http\Controllers\ScreeningQuestionnaire;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DevScreeningQuestionnaire\AnswerDevScreeningQuestionnaireRequest;
use App\Http\Requests\DevScreeningQuestionnaire\ShowDevScreeningQuestionnaireRequest;
use App\Http\Requests\ScreeningQuestionnaire\DestroyScreeningQuestionnaireRequest;
use App\Http\Requests\ScreeningQuestionnaire\ShowScreeningQuestionnaireRequest;
use App\Http\Requests\ScreeningQuestionnaire\StoreScreeningQuestionnaireRequest;
use App\Http\Requests\ScreeningQuestionnaire\UpdateScreeningQuestionnaireRequest;
use App\Services\ScreeningQuestionnaire\ScreeningQuestionnaireService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class ScreeningQuestionnaireController extends Controller
{
    public function __construct(protected ScreeningQuestionnaireService $screeningQuestionnaireService) {}

    #[Endpoint(operationId: 'storeScreeningQuestionnaire', title: 'Criar questionário de triagem da vaga', description: '**operationId:** `storeScreeningQuestionnaire` — Cria o questionário de triagem da vaga informada, com as perguntas e, nas de escolha, as alternativas (exige role `company` e que a vaga pertença ao perfil autenticado, caso contrário **403**). A vaga precisa ter a etapa `screening_questions` no processo seletivo e ainda não ter um questionário. Cada pergunta tem `type` `essay` (dissertativa, sem alternativas), `single_choice` (escolha única) ou `multiple_choice` (múltipla escolha) — as de escolha exigem no mínimo duas alternativas. O título, a descrição, as perguntas e as alternativas entram na fila de tradução (pt/en). Se a vaga já estava em `awaiting_screening_questions`, o cadastro inicia a etapa: as candidaturas paradas na espera passam para `screening_questions` e recebem o questionário com prazo em `due_date` (opcional, padrão de 7 dias). Em **201**, `data` segue o schema **Screening Questionnaire Resource** (`App\\Http\\Resources\\ScreeningQuestionnaire\\ScreeningQuestionnaireResource`).')]
    public function store(StoreScreeningQuestionnaireRequest $request): JsonResponse {

        try {
            $data = $this->screeningQuestionnaireService->store($request->validated());

            return ApiResponse::success(
                $data,
                'Screening questionnaire created with success!',
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

    #[Endpoint(operationId: 'showScreeningQuestionnaire', title: 'Ver questionário de triagem da vaga', description: '**operationId:** `showScreeningQuestionnaire` — Retorna o questionário de triagem da vaga informada, com as perguntas e as alternativas na ordem definida pela empresa, e `dev_questionnaires_count` com quantos candidatos já o receberam (exige role `company` e que a vaga pertença ao perfil autenticado, caso contrário **403**). Vaga sem questionário cadastrado responde **404**. Em **200**, `data` segue o schema **Screening Questionnaire Resource** (`App\\Http\\Resources\\ScreeningQuestionnaire\\ScreeningQuestionnaireResource`).')]
    public function show(ShowScreeningQuestionnaireRequest $request): JsonResponse {

        try {
            $data = $this->screeningQuestionnaireService->showByJobVacancy($request->validated());

            return ApiResponse::success(
                $data,
                'Screening questionnaire retrieved with success!',
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

    #[Endpoint(operationId: 'updateScreeningQuestionnaire', title: 'Atualizar questionário de triagem', description: '**operationId:** `updateScreeningQuestionnaire` — Atualiza o título, a descrição e as perguntas do questionário (exige role `company` e que o questionário seja de uma vaga do perfil autenticado, caso contrário **403**). A lista de perguntas enviada substitui a atual: as que vierem com `id` são atualizadas, as sem `id` são criadas e as que ficarem de fora são removidas — o mesmo vale para as alternativas de cada pergunta. Só é aceito enquanto a etapa não começou, ou seja, enquanto o questionário não foi enviado a nenhum candidato. Os textos alterados voltam para a fila de tradução. Em **200**, `data` segue o schema **Screening Questionnaire Resource** (`App\\Http\\Resources\\ScreeningQuestionnaire\\ScreeningQuestionnaireResource`).')]
    public function update(UpdateScreeningQuestionnaireRequest $request): JsonResponse {

        try {
            $data = $this->screeningQuestionnaireService->update($request->validated());

            return ApiResponse::success(
                $data,
                'Screening questionnaire updated with success!',
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

    #[Endpoint(operationId: 'destroyScreeningQuestionnaire', title: 'Remover questionário de triagem', description: '**operationId:** `destroyScreeningQuestionnaire` — Remove o questionário da vaga, junto com as perguntas e as alternativas (exige role `company` e que o questionário seja de uma vaga do perfil autenticado, caso contrário **403**). Só é aceito enquanto o questionário não foi enviado a nenhum candidato. Em **200**, `data` vem `null`.')]
    public function destroy(DestroyScreeningQuestionnaireRequest $request): JsonResponse {

        try {
            $this->screeningQuestionnaireService->destroy($request->validated());

            return ApiResponse::success(
                null,
                'Screening questionnaire deleted with success!',
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

    #[Endpoint(operationId: 'showDevScreeningQuestionnaire', title: 'Ver questionário de triagem a responder', description: '**operationId:** `showDevScreeningQuestionnaire` — Retorna o preenchimento do questionário de triagem do desenvolvedor autenticado, com o questionário completo (perguntas e alternativas) e, se já tiver respondido, as respostas enviadas (exige role `dev`; preenchimentos de outro desenvolvedor respondem **403**). O `id` é o do preenchimento, que chega no campo `screening_questionnaire` da candidatura. Em **200**, `data` segue o schema **Dev Screening Questionnaire Resource** (`App\\Http\\Resources\\DevScreeningQuestionnaire\\DevScreeningQuestionnaireResource`).')]
    public function showDevQuestionnaire(ShowDevScreeningQuestionnaireRequest $request): JsonResponse {

        try {
            $data = $this->screeningQuestionnaireService->showDevQuestionnaire($request->validated());

            return ApiResponse::success(
                $data,
                'Screening questionnaire retrieved with success!',
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

    #[Endpoint(operationId: 'answerDevScreeningQuestionnaire', title: 'Responder questionário de triagem', description: '**operationId:** `answerDevScreeningQuestionnaire` — Envia as respostas do desenvolvedor autenticado (exige role `dev`; preenchimentos de outro desenvolvedor respondem **403**). Só é aceito uma vez, com a candidatura em andamento e parada na etapa `screening_questions`. Cada item de `answers` traz o `question_id` e, conforme o tipo da pergunta, `response` (dissertativa) ou `option_ids` (uma opção na escolha única, uma ou mais na múltipla escolha). Todas as perguntas com `is_required` precisam vir respondidas. O preenchimento passa para `answered` e a empresa é notificada. Em **200**, `data` segue o schema **Dev Screening Questionnaire Resource** (`App\\Http\\Resources\\DevScreeningQuestionnaire\\DevScreeningQuestionnaireResource`).')]
    public function answer(AnswerDevScreeningQuestionnaireRequest $request): JsonResponse {

        try {
            $data = $this->screeningQuestionnaireService->answer($request->validated());

            return ApiResponse::success(
                $data,
                'Screening questionnaire answered with success!',
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
