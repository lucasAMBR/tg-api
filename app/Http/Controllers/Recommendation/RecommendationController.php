<?php

namespace App\Http\Controllers\Recommendation;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recommendation\RecommendationDevsRequest;
use App\Services\Recommendation\RecommendationService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class RecommendationController extends Controller
{
    public function __construct(protected RecommendationService $recommendationService) {}

    #[Endpoint(operationId: 'recommendDevsForJobVacancy', title: 'Recomendar desenvolvedores para uma vaga', description: '**operationId:** `recommendDevsForJobVacancy` — Retorna os desenvolvedores mais aderentes à vaga, comparando o embedding da vaga com o embedding de cada perfil por similaridade de cosseno. Aceita `limit` (padrão 10) e `min_similarity` como filtros. Exige role `company`, que a vaga pertença ao perfil autenticado e que o embedding da vaga já tenha sido gerado. Em **200**, `data[]` segue o schema **Recommended Dev Resource** (`App\\Http\\Resources\\Recommendation\\RecommendedDevResource`), com `similarity` (0 a 1) e `dev_profile` no schema **Dev Profile Resource** (`App\\Http\\Resources\\Profiles\\DevProfile\\DevProfileResource`).')]
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
}
