<?php

namespace App\Http\Controllers\DevJobVacancyInterview;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DevJobVacancyInterview\CancelDevJobVacancyInterviewRequest;
use App\Http\Requests\DevJobVacancyInterview\CompanyAcceptScheduleDevJobVacancyInterviewRequest;
use App\Http\Requests\DevJobVacancyInterview\CompanyProposeScheduleDevJobVacancyInterviewRequest;
use App\Http\Requests\DevJobVacancyInterview\DevAcceptScheduleDevJobVacancyInterviewRequest;
use App\Http\Requests\DevJobVacancyInterview\DevProposeScheduleDevJobVacancyInterviewRequest;
use App\Http\Requests\DevJobVacancyInterview\JoinDevJobVacancyInterviewRequest;
use App\Http\Requests\DevJobVacancyInterview\LeaveDevJobVacancyInterviewRequest;
use App\Http\Requests\DevJobVacancyInterview\SetInitialScheduleDevJobVacancyInterviewRequest;
use App\Http\Requests\DevJobVacancyInterview\SignalDevJobVacancyInterviewRequest;
use App\Services\DevJobVacancyInterview\DevJobVacancyInterviewService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class DevJobVacancyInterviewController extends Controller
{
    public function __construct(protected DevJobVacancyInterviewService $devJobVacancyInterviewService) {}

    #[Endpoint(operationId: 'setInitialScheduleDevJobVacancyInterview', title: 'Definir horário inicial da entrevista', description: '**operationId:** `setInitialScheduleDevJobVacancyInterview` — Registra o primeiro horário proposto para a entrevista, com `scheduled_at` e `duration_in_minutes` (exige role `company` e que a entrevista pertença a uma vaga do perfil autenticado, caso contrário **403**; só é aceito quando o status ainda é `awaiting_schedule`). O status passa para `awaiting_dev_confirmation` e o dev é notificado. Em **200**, `data` segue o schema **Dev Job Vacancy Interview Resource** (`App\\Http\\Resources\\DevJobVacancyInterview\\DevJobVacancyInterviewResource`).')]
    public function setInitialSchedule(SetInitialScheduleDevJobVacancyInterviewRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyInterviewService->setInitialSchedule($request->validated());

            return ApiResponse::success(
                $data,
                'Interview schedule set with success!',
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

    #[Endpoint(operationId: 'companyProposeScheduleDevJobVacancyInterview', title: 'Empresa propor novo horário da entrevista', description: '**operationId:** `companyProposeScheduleDevJobVacancyInterview` — Responde à contraproposta de horário feita pelo dev com um novo `scheduled_at`/`duration_in_minutes` (exige role `company` e que a entrevista pertença a uma vaga do perfil autenticado, caso contrário **403**; só é aceito quando o status é `awaiting_company_confirmation`). O status volta para `awaiting_dev_confirmation` e o dev é notificado. Em **200**, `data` segue o schema **Dev Job Vacancy Interview Resource** (`App\\Http\\Resources\\DevJobVacancyInterview\\DevJobVacancyInterviewResource`).')]
    public function companyProposeSchedule(CompanyProposeScheduleDevJobVacancyInterviewRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyInterviewService->companyProposeSchedule($request->validated());

            return ApiResponse::success(
                $data,
                'Interview schedule proposed with success!',
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

    #[Endpoint(operationId: 'devProposeScheduleDevJobVacancyInterview', title: 'Dev propor novo horário da entrevista', description: '**operationId:** `devProposeScheduleDevJobVacancyInterview` — Responde à proposta de horário feita pela empresa com um novo `scheduled_at`/`duration_in_minutes` (exige role `dev` e que a entrevista pertença a uma candidatura do perfil autenticado, caso contrário **403**; só é aceito quando o status é `awaiting_dev_confirmation`). O status passa para `awaiting_company_confirmation` e a empresa é notificada. Em **200**, `data` segue o schema **Dev Job Vacancy Interview Resource** (`App\\Http\\Resources\\DevJobVacancyInterview\\DevJobVacancyInterviewResource`).')]
    public function devProposeSchedule(DevProposeScheduleDevJobVacancyInterviewRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyInterviewService->devProposeSchedule($request->validated());

            return ApiResponse::success(
                $data,
                'Interview schedule proposed with success!',
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

    #[Endpoint(operationId: 'devAcceptScheduleDevJobVacancyInterview', title: 'Dev aceitar horário da entrevista', description: '**operationId:** `devAcceptScheduleDevJobVacancyInterview` — Aceita o horário vigente da entrevista, proposto pela empresa (exige role `dev` e que a entrevista pertença a uma candidatura do perfil autenticado, caso contrário **403**; só é aceito quando o status é `awaiting_dev_confirmation`). O status passa para `approved` e a empresa é notificada. Em **200**, `data` segue o schema **Dev Job Vacancy Interview Resource** (`App\\Http\\Resources\\DevJobVacancyInterview\\DevJobVacancyInterviewResource`).')]
    public function devAcceptSchedule(DevAcceptScheduleDevJobVacancyInterviewRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyInterviewService->devAcceptSchedule($request->validated());

            return ApiResponse::success(
                $data,
                'Interview schedule accepted with success!',
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

    #[Endpoint(operationId: 'companyAcceptScheduleDevJobVacancyInterview', title: 'Empresa aceitar horário da entrevista', description: '**operationId:** `companyAcceptScheduleDevJobVacancyInterview` — Aceita o horário vigente da entrevista, proposto pelo dev (exige role `company` e que a entrevista pertença a uma vaga do perfil autenticado, caso contrário **403**; só é aceito quando o status é `awaiting_company_confirmation`). O status passa para `approved` e o dev é notificado. Em **200**, `data` segue o schema **Dev Job Vacancy Interview Resource** (`App\\Http\\Resources\\DevJobVacancyInterview\\DevJobVacancyInterviewResource`).')]
    public function companyAcceptSchedule(CompanyAcceptScheduleDevJobVacancyInterviewRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyInterviewService->companyAcceptSchedule($request->validated());

            return ApiResponse::success(
                $data,
                'Interview schedule accepted with success!',
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

    #[Endpoint(operationId: 'cancelDevJobVacancyInterview', title: 'Cancelar entrevista', description: '**operationId:** `cancelDevJobVacancyInterview` — Cancela a entrevista, em qualquer etapa da negociação (inclusive já aprovada), e avisa a outra parte (exige role `dev` ou `company` e que a entrevista pertença ao usuário autenticado, caso contrário **403**; não é aceito quando o status já é `cancelled` ou `rejected`). O status passa para `cancelled`. Em **200**, `data` segue o schema **Dev Job Vacancy Interview Resource** (`App\\Http\\Resources\\DevJobVacancyInterview\\DevJobVacancyInterviewResource`).')]
    public function cancel(CancelDevJobVacancyInterviewRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyInterviewService->cancel($request->validated());

            return ApiResponse::success(
                $data,
                'Interview cancelled with success!',
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

    #[Endpoint(operationId: 'joinDevJobVacancyInterview', title: 'Entrar na call da entrevista', description: '**operationId:** `joinDevJobVacancyInterview` — Valida o acesso à call e devolve o necessário para o front montar o `RTCPeerConnection` (exige role `dev` ou `company` e que a entrevista pertença ao usuário autenticado, caso contrário **403**; só é aceito quando o status é `approved` e dentro da janela de tolerância em torno do `scheduled_at`). Marca `started_at` na primeira entrada. Em **200**, `data.channel` traz o canal privado de sinalização, `data.role` (`dev`/`company`), `data.ice_servers` a lista de servidores STUN/TURN e `data.interview` segue o schema **Dev Job Vacancy Interview Resource** (`App\\Http\\Resources\\DevJobVacancyInterview\\DevJobVacancyInterviewResource`).')]
    public function join(JoinDevJobVacancyInterviewRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyInterviewService->join($request->validated());

            return ApiResponse::success(
                $data,
                'Interview call joined with success!',
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

    #[Endpoint(operationId: 'leaveDevJobVacancyInterview', title: 'Sair da call da entrevista', description: '**operationId:** `leaveDevJobVacancyInterview` — Marca `ended_at` na primeira saída de qualquer um dos dois participantes, encerrando a call (exige role `dev` ou `company` e que a entrevista pertença ao usuário autenticado, caso contrário **403**). Em **200**, `data` segue o schema **Dev Job Vacancy Interview Resource** (`App\\Http\\Resources\\DevJobVacancyInterview\\DevJobVacancyInterviewResource`).')]
    public function leave(LeaveDevJobVacancyInterviewRequest $request): JsonResponse {

        try {
            $data = $this->devJobVacancyInterviewService->leave($request->validated());

            return ApiResponse::success(
                $data,
                'Interview call left with success!',
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

    #[Endpoint(operationId: 'signalDevJobVacancyInterview', title: 'Repassar sinal WebRTC da call', description: '**operationId:** `signalDevJobVacancyInterview` — Repassa um sinal WebRTC (`type`: `offer`, `answer` ou `candidate`, com o `payload` correspondente) para o outro participante da entrevista, via o evento `signal` no canal privado `interview.{id}` (exige role `dev` ou `company` e que a entrevista pertença ao usuário autenticado, caso contrário **403**; só é aceito quando o status é `approved`). O backend nunca vê o conteúdo da call em si, só essa etapa de sinalização. Não retorna dados em `data`.')]
    public function signal(SignalDevJobVacancyInterviewRequest $request): JsonResponse {

        try {
            $this->devJobVacancyInterviewService->signal($request->validated());

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
