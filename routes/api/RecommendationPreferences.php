<?php

use App\Http\Controllers\RecommendationPreference\RecommendationPreferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:api")->group(function () {
    Route::get('/{dev_profile_id}', [RecommendationPreferenceController::class, 'getPreferences']);
    Route::patch('/{dev_profile_id}', [RecommendationPreferenceController::class, 'updatePreference']);
});
