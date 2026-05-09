<?php

use App\Http\Controllers\RecommendationPreference\RecommendationPreferenceController;
use Illuminate\Support\Facades\Route;

Route::middleware("auth:api")->group(function () {
    Route::get('/{devProfile}', [RecommendationPreferenceController::class, 'getPreferences']);
    Route::patch('/{profile}', [RecommendationPreferenceController::class, 'updatePreference']);
});
