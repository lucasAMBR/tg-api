<?php

namespace App\Http\Controllers\RecommendationPreference;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\RecommendationPreference\UpdateRecommendationPreference;
use App\Models\DevProfile;
use App\Services\RecommendationPreference\RecommendationPreferenceService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RecommendationPreferenceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected RecommendationPreferenceService $recommendationPreferenceService){}

    public function getPreferences(DevProfile $devProfile)
    {
        $preference = $this->recommendationPreferenceService->getDevProfileRecommendationPreferences($devProfile);

        return ApiResponse::success($preference, "Preferences found with success!");
    }

    public function updatePreference(DevProfile $profile, UpdateRecommendationPreference $request)
    {
        $preference = $this->recommendationPreferenceService->updateRecommendationPreference($profile, $request->validated());

        return ApiResponse::success($preference, "Preferences updated!");
    }
}
