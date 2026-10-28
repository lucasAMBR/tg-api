<?php

namespace App\Http\Controllers\TechnicalChallenge;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DevTechnicalChallenge\ShowDevTechnicalChallengeRequest;
use App\Http\Requests\DevTechnicalChallenge\SubmitDevTechnicalChallengeRequest;
use App\Http\Requests\TechnicalChallenge\DestroyTechnicalChallengeRequest;
use App\Http\Requests\TechnicalChallenge\RemoveGuideDocumentRequest;
use App\Http\Requests\TechnicalChallenge\SaveGuideDocumentRequest;
use App\Http\Requests\TechnicalChallenge\ShowTechnicalChallengeRequest;
use App\Http\Requests\TechnicalChallenge\StoreTechnicalChallengeRequest;
use App\Http\Requests\TechnicalChallenge\UpdateTechnicalChallengeRequest;
use App\Services\TechnicalChallenge\TechnicalChallengeService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class TechnicalChallengeController extends Controller
{
    public function __construct(protected TechnicalChallengeService $technicalChallengeService) {}

    #[Endpoint(operationId: 'storeTechnicalChallenge', title: 'Criar desafio técnico da vaga', description: '**operationId:** `storeTechnicalChallenge` — Cria o desafio técnico da vaga informada, com o enunciado, as regras, o prazo de entrega e, opcionalmente, o documento guia em `guide_document` (exige role `company` e que a vaga pertença ao perfil autenticado, caso contrário **403**). A vaga precisa ter a etapa `technical_challenge` no processo seletivo e ainda não ter um desafio. Com `accepts_zip_submission` a empresa libera o envio do projeto compactado além do repositório. O título, a descrição e as regras entram na fila de tradução (pt/en). Se a vaga já estava em `awaiting_technical_challenge`, o cadastro inicia a etapa: as candidaturas paradas na espera passam para `technical_challenge` e recebem o desafio com o prazo em `due_date`. O envio é `multipart/form-data` quando traz o documento guia. Em **201**, `data` segue o schema **Technical Challenge Resource** (`App\\Http\\Resources\\TechnicalChallenge\\TechnicalChallengeResource`).')]
    public function store(StoreTechnicalChallengeRequest $request): JsonResponse {

        try {
            $data = $this->technicalChallengeService->store($request->validated());

            return ApiResponse::success(
                $data,
                'Technical challenge created with success!',
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

    #[Endpoint(operationId: 'showTechnicalChallenge', title: 'Ver desafio técnico da vaga', description: '**operationId:** `showTechnicalChallenge` — Retorna o desafio técnico da vaga informada, com o enunciado, as regras, o documento guia e `dev_challenges_count` com quantos candidatos já o receberam (exige role `company` e que a vaga pertença ao perfil autenticado, caso contrário **403**). Vaga sem desafio cadastrado responde **404**. Em **200**, `data` segue o schema **Technical Challenge Resource** (`App\\Http\\Resources\\TechnicalChallenge\\TechnicalChallengeResource`).')]
    public function show(ShowTechnicalChallengeRequest $request): JsonResponse {

        try {
            $data = $this->technicalChallengeService->showByJobVacancy($request->validated());

            return ApiResponse::success(
                $data,
                'Technical challenge retrieved with success!',
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

    #[Endpoint(operationId: 'updateTechnicalChallenge', title: 'Atualizar desafio técnico', description: '**operationId:** `updateTechnicalChallenge` — Atualiza o enunciado, as regras, o prazo de entrega e a permissão de envio do zip do desafio (exige role `company` e que o desafio seja de uma vaga do perfil autenticado, caso contrário **403**). Só é aceito enquanto a etapa não começou, ou seja, enquanto o desafio não foi enviado a nenhum candidato. Os textos alterados voltam para a fila de tradução. O documento guia é trocado pelos endpoints próprios. Em **200**, `data` segue o schema **Technical Challenge Resource** (`App\\Http\\Resources\\TechnicalChallenge\\TechnicalChallengeResource`).')]
    public function update(UpdateTechnicalChallengeRequest $request): JsonResponse {

        try {
            $data = $this->technicalChallengeService->update($request->validated());

            return ApiResponse::success(
                $data,
                'Technical challenge updated with success!',
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

    #[Endpoint(operationId: 'destroyTechnicalChallenge', title: 'Remover desafio técnico', description: '**operationId:** `destroyTechnicalChallenge` — Remove o desafio da vaga, junto com o documento guia (exige role `company` e que o desafio seja de uma vaga do perfil autenticado, caso contrário **403**). Só é aceito enquanto o desafio não foi enviado a nenhum candidato. Em **200**, `data` vem `null`.')]
    public function destroy(DestroyTechnicalChallengeRequest $request): JsonResponse {

        try {
            $this->technicalChallengeService->destroy($request->validated());

            return ApiResponse::success(
                null,
                'Technical challenge deleted with success!',
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

    #[Endpoint(operationId: 'saveTechnicalChallengeGuideDocument', title: 'Anexar documento guia do desafio', description: '**operationId:** `saveTechnicalChallengeGuideDocument` — Anexa o documento guia ao desafio, em `multipart/form-data` (exige role `company` e que o desafio seja de uma vaga do perfil autenticado, caso contrário **403**). A coleção é de arquivo único: o envio substitui o documento anterior. Diferente do resto do desafio, o guia pode ser trocado mesmo com a etapa em andamento. Em **200**, `data` segue o schema **Technical Challenge Resource** (`App\\Http\\Resources\\TechnicalChallenge\\TechnicalChallengeResource`).')]
    public function saveGuideDocument(SaveGuideDocumentRequest $request): JsonResponse {

        try {
            $data = $this->technicalChallengeService->saveGuideDocumentFile($request->validated());

            return ApiResponse::success(
                $data,
                'Technical challenge guide document saved with success!',
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

    #[Endpoint(operationId: 'removeTechnicalChallengeGuideDocument', title: 'Remover documento guia do desafio', description: '**operationId:** `removeTechnicalChallengeGuideDocument` — Remove o documento guia do desafio (exige role `company` e que o desafio seja de uma vaga do perfil autenticado, caso contrário **403**). Em **200**, `data` segue o schema **Technical Challenge Resource** (`App\\Http\\Resources\\TechnicalChallenge\\TechnicalChallengeResource`), com `guide_document` em `null`.')]
    public function removeGuideDocument(RemoveGuideDocumentRequest $request): JsonResponse {

        try {
            $data = $this->technicalChallengeService->removeGuideDocument($request->validated());

            return ApiResponse::success(
                $data,
                'Technical challenge guide document removed with success!',
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

    #[Endpoint(operationId: 'showDevTechnicalChallenge', title: 'Ver desafio técnico a entregar', description: '**operationId:** `showDevTechnicalChallenge` — Retorna a entrega do desafio técnico do desenvolvedor autenticado, com o enunciado, as regras, o documento guia e, se já tiver entregue, o repositório e o zip enviados (exige role `dev`; entregas de outro desenvolvedor respondem **403**). O `id` é o da entrega, que chega no campo `technical_challenge` da candidatura. Em **200**, `data` segue o schema **Dev Technical Challenge Resource** (`App\\Http\\Resources\\DevTechnicalChallenge\\DevTechnicalChallengeResource`).')]
    public function showDevChallenge(ShowDevTechnicalChallengeRequest $request): JsonResponse {

        try {
            $data = $this->technicalChallengeService->showDevChallenge($request->validated());

            return ApiResponse::success(
                $data,
                'Technical challenge retrieved with success!',
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

    #[Endpoint(operationId: 'submitDevTechnicalChallenge', title: 'Entregar desafio técnico', description: '**operationId:** `submitDevTechnicalChallenge` — Envia a solução do desenvolvedor autenticado, em `multipart/form-data` (exige role `dev`; entregas de outro desenvolvedor respondem **403**). Só é aceito uma vez, com a candidatura em andamento e parada na etapa `technical_challenge`. O `repository_url` é obrigatório e o `submission_file` (zip) só é aceito quando o desafio tem `accepts_zip_submission`. A entrega passa para `delivered` e a empresa é notificada. Em **200**, `data` segue o schema **Dev Technical Challenge Resource** (`App\\Http\\Resources\\DevTechnicalChallenge\\DevTechnicalChallengeResource`).')]
    public function submit(SubmitDevTechnicalChallengeRequest $request): JsonResponse {

        try {
            $data = $this->technicalChallengeService->submit($request->validated());

            return ApiResponse::success(
                $data,
                'Technical challenge delivered with success!',
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
