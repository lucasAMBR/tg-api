<?php

namespace App\Http\Controllers\Dashboard;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\ShowDashboardRequest;
use App\Services\Dashboard\DashboardService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService){}

    #[Endpoint(operationId: 'showCompanyDashboard', title: 'Dashboard da empresa', description: '**operationId:** `showCompanyDashboard` — Métricas das vagas da empresa autenticada (o admin também acessa quando está usando o perfil de empresa; com outro perfil ativo responde **403**). Em **200**, `data` traz: `summary` (totais de vagas e candidaturas, contratados, `hire_rate` em %, média de candidaturas por vaga e candidaturas dos últimos 30 dias); `agenda` (entrevistas e live codings marcados e os que aguardam a empresa confirmar o horário); `vacancies_by_status` e `applications_by_status` com todos os casos do enum; `vacancies_by_process_step` (vagas com inscrições encerradas por etapa, com `awaiting_count` das que esperam a empresa preparar a etapa); `applications_by_process_step` (candidaturas em andamento e recusadas em cada etapa); `applications_by_vacancy` (as 10 vagas com mais candidaturas); `applicants_by_specialty` e `applicants_by_seniority`; e `timeline` com vagas criadas e candidaturas recebidas por mês. `months` (1 a 24, padrão 6) define quantos meses a `timeline` cobre.')]
    public function company(ShowDashboardRequest $request): JsonResponse
    {
        try {
            $dashboard = $this->dashboardService->company($request->validated());

            return ApiResponse::success($dashboard, "Company dashboard loaded with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showAdminDashboard', title: 'Dashboard do admin', description: '**operationId:** `showAdminDashboard` — Métricas da plataforma inteira. Em **200**, `data` traz: `summary` (usuários, perfis de dev, empresa, cliente e admin, bloqueados, cadastros dos últimos 30 dias, vagas, candidaturas, contratados, freelas, orçamentos e o volume contratado em freelas); `specialties` (devs, vagas e candidaturas por especialidade); `devs_by_seniority`; `companies_by_segment`; `job_vacancies_by_status`, `applications_by_status`, `freelance_vacancies_by_status` e `freelance_proposals_by_status` com todos os casos do enum; `top_dev_languages` e `top_vacancy_languages` (as 10 linguagens mais comuns entre os devs e mais exigidas nas vagas); `social` (posts, comentários, curtidas, favoritos, follows, conversas e mensagens do chat, e `interactions`); e `timeline` com cadastros, candidaturas, posts, comentários e curtidas por mês. Posts excluídos e comentários removidos não contam. `months` (1 a 24, padrão 6) define quantos meses a `timeline` cobre.')]
    public function admin(ShowDashboardRequest $request): JsonResponse
    {
        try {
            $dashboard = $this->dashboardService->admin($request->validated());

            return ApiResponse::success($dashboard, "Admin dashboard loaded with success!");
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
