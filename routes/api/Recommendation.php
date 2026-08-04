<?php

use App\Http\Controllers\Recommendation\RecommendationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    Route::get('/job-vacancy/{job_vacancy_id}/devs', [RecommendationController::class, 'devsForJobVacancy']);
});
