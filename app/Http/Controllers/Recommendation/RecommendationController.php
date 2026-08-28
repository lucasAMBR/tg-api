<?php

namespace App\Http\Controllers\Recommendation;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recommendation\RecommendationDevsForFreelanceRequest;
use App\Http\Requests\Recommendation\RecommendationDevsRequest;
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

    #[Endpoint(operationId: 'recommendDevsForFreelanceJobVacancy', title: 'Recomendar desenvolvedores para uma vaga freelance', description: '**operationId:** `recommendDevsForFreelanceJobVacancy` — Retorna os desenvolvedores mais aderentes à vaga freelance, comparando o embedding da vaga com o embedding de cada perfil por similaridade de cosseno. Aceita `limit` (padrão 10) e `min_similarity` como filtros. Só retorna devs cujas **preferências de recomendação** são compatíveis com trabalho freelance: `allow_contractor` e `allow_remote` verdadeiros, nenhuma das linguagens exigidas na blacklist e, quando `allow_stack_flexibility` é falso, ao menos uma linguagem exigida entre as hard skills do dev. Diferente da vaga CLT, `min_remuneration` e distância não são considerados: a vaga freelance não tem modalidade e sua remuneração pode ser por dia, semana ou mês. Exige role `client`, que a vaga pertença ao perfil autenticado e que o embedding da vaga já tenha sido gerado. Em **200**, `data[]` segue o schema **Recommended Dev Resource** (`App\\Http\\Resources\\Recommendation\\RecommendedDevResource`), com `similarity` (0 a 1) e `dev_profile` no schema **Dev Profile Resource** (`App\\Http\\Resources\\Profiles\\DevProfile\\DevProfileResource`).')]
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
}
