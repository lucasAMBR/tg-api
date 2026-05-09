<?php

namespace App\Services\RecommendationPreference;

use App\Http\Resources\RecommendationPreference\RecommendationPreferenceResource;
use App\Models\DevProfile;

class RecommendationPreferenceService
{
    public function getDevProfileRecommendationPreferences(DevProfile $devProfile)
    {
        $recommendation = $devProfile->recommendation_preference;

        return new RecommendationPreferenceResource($recommendation);
    }

    public function updateRecommendationPreference(DevProfile $profile, array $data)
    {
        $recommendation = $profile->recommendation_preference;

        $recommendation->update($data);

        if(isset($data['languages_blacklist'])){
            $recommendation->blackListedLanguages()->sync($data['languages_blacklist']);
        }

        $recommendation->refresh();

        return new RecommendationPreferenceResource($recommendation);
    }
}
