<?php

namespace App\Services\RecommendationPreference;

use App\Http\Resources\RecommendationPreference\RecommendationPreferenceResource;
use App\Models\DevProfile;
use Illuminate\Support\Arr;

class RecommendationPreferenceService
{
    public function getDevProfileRecommendationPreferences(array $data)
    {
        $devProfile = DevProfile::findOrFail($data['dev_profile_id']);

        $recommendation = $devProfile->recommendation_preference;

        return new RecommendationPreferenceResource($recommendation);
    }

    public function updateRecommendationPreference(array $data)
    {
        $profile = DevProfile::findOrFail($data['dev_profile_id']);

        $recommendation = $profile->recommendation_preference;

        $data = Arr::except($data, ['dev_profile_id']);

        $recommendation->update($data);

        if(isset($data['languages_blacklist'])){
            $recommendation->blackListedLanguages()->sync($data['languages_blacklist']);
        }

        $recommendation->refresh();

        return new RecommendationPreferenceResource($recommendation);
    }
}
