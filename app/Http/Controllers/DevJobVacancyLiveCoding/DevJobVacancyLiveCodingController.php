<?php

namespace App\Http\Controllers\DevJobVacancyLiveCoding;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DevJobVacancyLiveCoding\CancelDevJobVacancyLiveCodingRequest;
use App\Http\Requests\DevJobVacancyLiveCoding\CompanyAcceptScheduleDevJobVacancyLiveCodingRequest;
use App\Http\Requests\DevJobVacancyLiveCoding\CompanyProposeScheduleDevJobVacancyLiveCodingRequest;
use App\Http\Requests\DevJobVacancyLiveCoding\DevAcceptScheduleDevJobVacancyLiveCodingRequest;
use App\Http\Requests\DevJobVacancyLiveCoding\DevProposeScheduleDevJobVacancyLiveCodingRequest;
use App\Http\Requests\DevJobVacancyLiveCoding\JoinDevJobVacancyLiveCodingRequest;
use App\Http\Requests\DevJobVacancyLiveCoding\LeaveDevJobVacancyLiveCodingRequest;
use App\Http\Requests\DevJobVacancyLiveCoding\SetExerciseDevJobVacancyLiveCodingRequest;
use App\Http\Requests\DevJobVacancyLiveCoding\SetInitialScheduleDevJobVacancyLiveCodingRequest;
use App\Http\Requests\DevJobVacancyLiveCoding\SignalDevJobVacancyLiveCodingRequest;
use App\Services\DevJobVacancyLiveCoding\DevJobVacancyLiveCodingService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class DevJobVacancyLiveCodingController extends Controller
{
    public function __construct(protected DevJobVacancyLiveCodingService $devJobVacancyLiveCodingService) {}

    #[Endpoint(operationId: 'setExerciseDevJobVacancyLiveCoding', title: 'Definir exercício do live coding', description: '**operationId:** `setExerciseDevJobVacancyLiveCoding` — Publica (ou corrige) o `statement` do exercício que o dev vai resolver na call, com a `programming_language` opcional (exige role `company` e que o live coding pertença a uma vaga do perfil autenticado, caso contrário **403**). Não é aceito quando o status já é `cancelled`/`rejected` nem depois que a call terminou (`ended_at` preenchido). O dev é notificado apenas na primeira publicação; as correções seguintes são silenciosas. Em **200**, `data` segue o schema **Dev Job Vacancy Live Coding Resource** (`App\\Http\\Resources\\DevJobVacancyLiveCoding\\DevJobVacancyLiveCodingResource`).')]
    public function setExercise(SetExerciseDevJobVacancyLiveCodingRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyLiveCodingService->setExercise($request->validated());

            return ApiResponse::success(
                $data,
                'Live coding exercise set with success!',
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

    #[Endpoint(operationId: 'setInitialScheduleDevJobVacancyLiveCoding', title: 'Definir horário inicial do live coding', description: '**operationId:** `setInitialScheduleDevJobVacancyLiveCoding` — Registra o primeiro horário proposto para o live coding, com `scheduled_at` e `duration_in_minutes` (exige role `company` e que o live coding pertença a uma vaga do perfil autenticado, caso contrário **403**; só é aceito quando o status ainda é `awaiting_schedule`). O status passa para `awaiting_dev_confirmation` e o dev é notificado. Em **200**, `data` segue o schema **Dev Job Vacancy Live Coding Resource** (`App\\Http\\Resources\\DevJobVacancyLiveCoding\\DevJobVacancyLiveCodingResource`).')]
    public function setInitialSchedule(SetInitialScheduleDevJobVacancyLiveCodingRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyLiveCodingService->setInitialSchedule($request->validated());

            return ApiResponse::success(
                $data,
                'Live coding schedule set with success!',
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

    #[Endpoint(operationId: 'companyProposeScheduleDevJobVacancyLiveCoding', title: 'Empresa propor novo horário do live coding', description: '**operationId:** `companyProposeScheduleDevJobVacancyLiveCoding` — Responde à contraproposta de horário feita pelo dev com um novo `scheduled_at`/`duration_in_minutes` (exige role `company` e que o live coding pertença a uma vaga do perfil autenticado, caso contrário **403**; só é aceito quando o status é `awaiting_company_confirmation`). O status volta para `awaiting_dev_confirmation` e o dev é notificado. Em **200**, `data` segue o schema **Dev Job Vacancy Live Coding Resource** (`App\\Http\\Resources\\DevJobVacancyLiveCoding\\DevJobVacancyLiveCodingResource`).')]
    public function companyProposeSchedule(CompanyProposeScheduleDevJobVacancyLiveCodingRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyLiveCodingService->companyProposeSchedule($request->validated());

            return ApiResponse::success(
                $data,
                'Live coding schedule proposed with success!',
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

    #[Endpoint(operationId: 'devProposeScheduleDevJobVacancyLiveCoding', title: 'Dev propor novo horário do live coding', description: '**operationId:** `devProposeScheduleDevJobVacancyLiveCoding` — Responde à proposta de horário feita pela empresa com um novo `scheduled_at`/`duration_in_minutes` (exige role `dev` e que o live coding pertença a uma candidatura do perfil autenticado, caso contrário **403**; só é aceito quando o status é `awaiting_dev_confirmation`). O status passa para `awaiting_company_confirmation` e a empresa é notificada. Em **200**, `data` segue o schema **Dev Job Vacancy Live Coding Resource** (`App\\Http\\Resources\\DevJobVacancyLiveCoding\\DevJobVacancyLiveCodingResource`).')]
    public function devProposeSchedule(DevProposeScheduleDevJobVacancyLiveCodingRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyLiveCodingService->devProposeSchedule($request->validated());

            return ApiResponse::success(
                $data,
                'Live coding schedule proposed with success!',
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

    #[Endpoint(operationId: 'devAcceptScheduleDevJobVacancyLiveCoding', title: 'Dev aceitar horário do live coding', description: '**operationId:** `devAcceptScheduleDevJobVacancyLiveCoding` — Aceita o horário vigente do live coding, proposto pela empresa (exige role `dev` e que o live coding pertença a uma candidatura do perfil autenticado, caso contrário **403**; só é aceito quando o status é `awaiting_dev_confirmation`). O status passa para `approved` e a empresa é notificada. Em **200**, `data` segue o schema **Dev Job Vacancy Live Coding Resource** (`App\\Http\\Resources\\DevJobVacancyLiveCoding\\DevJobVacancyLiveCodingResource`).')]
    public function devAcceptSchedule(DevAcceptScheduleDevJobVacancyLiveCodingRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyLiveCodingService->devAcceptSchedule($request->validated());

            return ApiResponse::success(
                $data,
                'Live coding schedule accepted with success!',
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

    #[Endpoint(operationId: 'companyAcceptScheduleDevJobVacancyLiveCoding', title: 'Empresa aceitar horário do live coding', description: '**operationId:** `companyAcceptScheduleDevJobVacancyLiveCoding` — Aceita o horário vigente do live coding, proposto pelo dev (exige role `company` e que o live coding pertença a uma vaga do perfil autenticado, caso contrário **403**; só é aceito quando o status é `awaiting_company_confirmation`). O status passa para `approved` e o dev é notificado. Em **200**, `data` segue o schema **Dev Job Vacancy Live Coding Resource** (`App\\Http\\Resources\\DevJobVacancyLiveCoding\\DevJobVacancyLiveCodingResource`).')]
    public function companyAcceptSchedule(CompanyAcceptScheduleDevJobVacancyLiveCodingRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyLiveCodingService->companyAcceptSchedule($request->validated());

            return ApiResponse::success(
                $data,
                'Live coding schedule accepted with success!',
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

    #[Endpoint(operationId: 'cancelDevJobVacancyLiveCoding', title: 'Cancelar live coding', description: '**operationId:** `cancelDevJobVacancyLiveCoding` — Cancela o live coding, em qualquer etapa da negociação (inclusive já aprovado), e avisa a outra parte (exige role `dev` ou `company` e que o live coding pertença ao usuário autenticado, caso contrário **403**; não é aceito quando o status já é `cancelled` ou `rejected`). O status passa para `cancelled`. Em **200**, `data` segue o schema **Dev Job Vacancy Live Coding Resource** (`App\\Http\\Resources\\DevJobVacancyLiveCoding\\DevJobVacancyLiveCodingResource`).')]
    public function cancel(CancelDevJobVacancyLiveCodingRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyLiveCodingService->cancel($request->validated());

            return ApiResponse::success(
                $data,
                'Live coding cancelled with success!',
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

    #[Endpoint(operationId: 'joinDevJobVacancyLiveCoding', title: 'Entrar na call do live coding', description: '**operationId:** `joinDevJobVacancyLiveCoding` — Valida o acesso à call e devolve o necessário para o front montar o `RTCPeerConnection` (exige role `dev` ou `company` e que o live coding pertença ao usuário autenticado, caso contrário **403**; só é aceito quando o status é `approved` e dentro da janela de tolerância em torno do `scheduled_at`). Marca `started_at` na primeira entrada. Em **200**, `data.channel` traz o canal privado de sinalização, `data.role` (`dev`/`company`), `data.ice_servers` a lista de servidores STUN/TURN e `data.live_coding` segue o schema **Dev Job Vacancy Live Coding Resource** (`App\\Http\\Resources\\DevJobVacancyLiveCoding\\DevJobVacancyLiveCodingResource`).')]
    public function join(JoinDevJobVacancyLiveCodingRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyLiveCodingService->join($request->validated());

            return ApiResponse::success(
                $data,
                'Live coding call joined with success!',
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

    #[Endpoint(operationId: 'leaveDevJobVacancyLiveCoding', title: 'Sair da call do live coding', description: '**operationId:** `leaveDevJobVacancyLiveCoding` — Marca `ended_at` na primeira saída de qualquer um dos dois participantes, encerrando a call (exige role `dev` ou `company` e que o live coding pertença ao usuário autenticado, caso contrário **403**). Em **200**, `data` segue o schema **Dev Job Vacancy Live Coding Resource** (`App\\Http\\Resources\\DevJobVacancyLiveCoding\\DevJobVacancyLiveCodingResource`).')]
    public function leave(LeaveDevJobVacancyLiveCodingRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyLiveCodingService->leave($request->validated());

            return ApiResponse::success(
                $data,
                'Live coding call left with success!',
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

    #[Endpoint(operationId: 'signalDevJobVacancyLiveCoding', title: 'Repassar sinal WebRTC da call', description: '**operationId:** `signalDevJobVacancyLiveCoding` — Repassa um sinal WebRTC (`type`: `offer`, `answer` ou `candidate`, com o `payload` correspondente) para o outro participante do live coding, via o evento `signal` no canal privado `live-coding.{id}` (exige role `dev` ou `company` e que o live coding pertença ao usuário autenticado, caso contrário **403**; só é aceito quando o status é `approved`). O backend nunca vê o conteúdo da call em si, só essa etapa de sinalização. Não retorna dados em `data`.')]
    public function signal(SignalDevJobVacancyLiveCodingRequest $request): JsonResponse {

        try {
            $this->devJobVacancyLiveCodingService->signal($request->validated());

            return ApiResponse::success(
                null,
                'Signal sent with success!',
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
