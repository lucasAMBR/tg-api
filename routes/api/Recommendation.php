<?php

use App\Http\Controllers\Recommendation\RecommendationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    Route::get('/job-vacancy/{job_vacancy_id}/devs', [RecommendationController::class, 'devsForJobVacancy']);
    // Freelas recomendados para o dev autenticado
    Route::get('/freelance-job-vacancy', [RecommendationController::class, 'freelanceJobVacanciesForDev']);
    Route::get('/freelance-job-vacancy/{freelance_job_vacancy_id}/devs', [RecommendationController::class, 'devsForFreelanceJobVacancy']);
    Route::get('/freelance-job-vacancy/{freelance_job_vacancy_id}/proposals', [RecommendationController::class, 'proposalsForFreelanceJobVacancy']);
});
