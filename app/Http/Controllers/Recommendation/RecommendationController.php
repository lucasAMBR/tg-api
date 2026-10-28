<?php

namespace App\Http\Controllers\Recommendation;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recommendation\RecommendationDevsForFreelanceRequest;
use App\Http\Requests\Recommendation\RecommendationDevsRequest;
use App\Http\Requests\Recommendation\RecommendationFreelanceJobVacanciesForDevRequest;
use App\Http\Requests\Recommendation\RecommendationProposalsForFreelanceRequest;
use App\Services\Recommendation\RecommendationService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class RecommendationController extends Controller
{
    public function __construct(protected RecommendationService $recommendationService) {}

    #[Endpoint(operationId: 'recommendDevsForJobVacancy', title: 'Recomendar desenvolvedores para uma vaga', description: '**operationId:** `recommendDevsForJobVacancy` — Retorna os desenvolvedores mais aderentes à vaga, comparando o embedding da vaga com o embedding de cada perfil por similaridade de cosseno. Aceita `limit` (padrão 10) e `min_similarity` como filtros. Só retorna devs cujas **preferências de recomendação** são compatíveis com a vaga: tipo de contrato (`allow_clt`, `allow_contractor`, `allow_internship`), modalidade (`allow_on_site`, `allow_hybrid`, `allow_remote`), `min_remuneration` menor ou igual ao salário estimado, nenhuma das linguagens exigidas na blacklist e, quando `allow_stack_flexibility` é falso, ao menos uma linguagem exigida entre as hard skills do dev. Distância só é considerada em vagas **presenciais** e **híbridas**: o dev precisa estar dentro de `on_site_job_radius` / `hybrid_jobs_radius` (km) do endereço da empresa — vagas remotas ignoram localização, devs com `open_to_relocation` ignoram o raio, e devs sem endereço cadastrado ficam de fora das vagas que exigem presença. Exige role `company`, que a vaga pertença ao perfil autenticado e que o embedding da vaga já tenha sido gerado. Em **200**, `data[]` segue o schema **Recommended Dev Resource** (`App\\Http\\Resources\\Recommendation\\RecommendedDevResource`), com `similarity` (0 a 1) e `dev_profile` no schema **Dev Profile Resource** (`App\\Http\\Resources\\Profiles\\DevProfile\\DevProfileResource`).')]
    public function devsForJobVacancy(RecommendationDevsRequest $request): JsonResponse {

        try {
            $devs = $this->recommendationService->recommendDevsForJobVacancy($request->validated());

            return ApiResponse::success(
                $devs,
                'Recommended developers retrieved with success!',
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

    #[Endpoint(operationId: 'recommendDevsForFreelanceJobVacancy', title: 'Recomendar desenvolvedores para uma vaga freelance', description: '**operationId:** `recommendDevsForFreelanceJobVacancy` — Retorna os desenvolvedores mais aderentes à vaga freelance, comparando o embedding da vaga com o embedding de cada perfil por similaridade de cosseno, tenham eles orçado ou não: cada um vem com `has_proposal` e `is_invited`, para o cliente convidar quem ainda não orçou (`POST /freelance-job-vacancy/{id}/invitations`). Aceita `limit` (padrão 10) e `min_similarity` como filtros. Só retorna devs cujas **preferências de recomendação** são compatíveis com trabalho freelance: `allow_contractor` e `allow_remote` verdadeiros, nenhuma das linguagens exigidas na blacklist e, quando `allow_stack_flexibility` é falso, ao menos uma linguagem exigida entre as hard skills do dev. Diferente da vaga CLT, `min_remuneration` e distância não são considerados: a vaga freelance não tem modalidade e o preço entra no ranking de orçamentos. Exige role `client`, que a vaga pertença ao perfil autenticado (senão **403**) e que o embedding da vaga já tenha sido gerado (senão **409**). Em **200**, `data[]` segue o schema **Recommended Freelance Dev Resource** (`App\\Http\\Resources\\Recommendation\\RecommendedFreelanceDevResource`), com `similarity` (0 a 1) e `dev_profile` no perfil público do dev.')]
    public function devsForFreelanceJobVacancy(RecommendationDevsForFreelanceRequest $request): JsonResponse {

        try {
            $devs = $this->recommendationService->recommendDevsForFreelanceJobVacancy($request->validated());

            return ApiResponse::success(
                $devs,
                'Recommended developers retrieved with success!',
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

    #[Endpoint(operationId: 'recommendProposalsForFreelanceJobVacancy', title: 'Ranquear os orçamentos de uma vaga freelance', description: '**operationId:** `recommendProposalsForFreelanceJobVacancy` — Retorna os orçamentos pendentes da vaga freelance do mais compatível para o menos. O `score` (0 a 1) soma, com os pesos de `config(\'app.freelance_proposal_ranking\')` (padrão 0,6 e 0,4): a `similarity` entre o embedding do dev e o da vaga, e o `price_score`, a proximidade do total do orçamento com o `budget` do cliente (menor valor dividido pelo maior: cobrar o dobro ou a metade do budget dá 0,5). Dev sem embedding entra só pelo preço, com `similarity: null`. Aceita `limit` (padrão 10, máximo 50). Exige role `client`, que a vaga pertença ao perfil autenticado (senão **403**) e que o embedding da vaga já tenha sido gerado (senão **409**). Em **200**, `data[]` segue o schema **Recommended Freelance Proposal Resource** (`App\\Http\\Resources\\Recommendation\\RecommendedFreelanceProposalResource`).')]
    public function proposalsForFreelanceJobVacancy(RecommendationProposalsForFreelanceRequest $request): JsonResponse {

        try {
            $proposals = $this->recommendationService->recommendProposalsForFreelanceJobVacancy($request->validated());

            return ApiResponse::success(
                $proposals,
                'Recommended proposals retrieved with success!',
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

    #[Endpoint(operationId: 'recommendFreelanceJobVacanciesForDev', title: 'Recomendar vagas freelance para o dev', description: '**operationId:** `recommendFreelanceJobVacanciesForDev` — Retorna os freelas abertos mais aderentes ao perfil do dev autenticado, comparando o embedding do perfil com o de cada freela por similaridade de cosseno. Saem os freelas em que o dev já orçou, os que exigem alguma linguagem da blacklist dele e, quando `allow_stack_flexibility` é falso, os que não pedem nenhuma linguagem entre as hard skills dele. Aceita `limit` (padrão 10, máximo 50) e `min_similarity`. Exige role `dev` e que o perfil já tenha embedding (senão **409**). Em **200**, `data[]` segue o schema **Recommended Freelance Job Vacancy Resource** (`App\\Http\\Resources\\Recommendation\\RecommendedFreelanceJobVacancyResource`), com `similarity` (0 a 1) e a vaga em `freelance_job_vacancy` (com `is_invited`).')]
    public function freelanceJobVacanciesForDev(RecommendationFreelanceJobVacanciesForDevRequest $request): JsonResponse {

        try {
            $freelanceJobVacancies = $this->recommendationService->recommendFreelanceJobVacanciesForDev($request->validated());

            return ApiResponse::success(
                $freelanceJobVacancies,
                'Recommended freelance job vacancies retrieved with success!',
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
