<?php

namespace App\Http\Controllers\RecommendationPreference;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecommendationPreference\GetRecommendationPreferenceRequest;
use App\Http\Requests\RecommendationPreference\UpdateRecommendationPreference;
use App\Services\RecommendationPreference\RecommendationPreferenceService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class RecommendationPreferenceController extends Controller
{
    public function __construct(protected RecommendationPreferenceService $recommendationPreferenceService){}

    #[Endpoint(operationId: 'getRecommendationPreferences', title: 'Consultar preferências de recomendação', description: '**operationId:** `getRecommendationPreferences` — Retorna as preferências de recomendação do perfil de desenvolvedor informado. Em **200**, `data` segue o schema **Recommendation Preference Resource** (`App\\Http\\Resources\\RecommendationPreference\\RecommendationPreferenceResource`).')]
    public function getPreferences(GetRecommendationPreferenceRequest $request): JsonResponse
    {
        try {
            $preference = $this->recommendationPreferenceService->getDevProfileRecommendationPreferences($request->validated());

            return ApiResponse::success($preference, "Preferences found with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateRecommendationPreference', title: 'Atualizar preferências de recomendação', description: '**operationId:** `updateRecommendationPreference` — Atualiza as preferências de recomendação do perfil de desenvolvedor informado. Quando `languages_blacklist` é enviado, a lista de linguagens bloqueadas é sincronizada. Em **200**, `data` segue o schema **Recommendation Preference Resource** (`App\\Http\\Resources\\RecommendationPreference\\RecommendationPreferenceResource`).')]
    public function updatePreference(UpdateRecommendationPreference $request): JsonResponse
    {
        try {
            $preference = $this->recommendationPreferenceService->updateRecommendationPreference($request->validated());

            return ApiResponse::success($preference, "Preferences updated!");
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
