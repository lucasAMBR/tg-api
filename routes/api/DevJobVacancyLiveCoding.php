<?php

use App\Http\Controllers\DevJobVacancyLiveCoding\DevJobVacancyLiveCodingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    // Enunciado do exercício, escrito pela empresa antes da call
    Route::patch('/{id}/set-exercise', [DevJobVacancyLiveCodingController::class, 'setExercise']);

    Route::patch('/{id}/set-schedule', [DevJobVacancyLiveCodingController::class, 'setInitialSchedule']);
    Route::patch('/{id}/company-propose-schedule', [DevJobVacancyLiveCodingController::class, 'companyProposeSchedule']);
    Route::patch('/{id}/dev-propose-schedule', [DevJobVacancyLiveCodingController::class, 'devProposeSchedule']);
    Route::patch('/{id}/dev-accept-schedule', [DevJobVacancyLiveCodingController::class, 'devAcceptSchedule']);
    Route::patch('/{id}/company-accept-schedule', [DevJobVacancyLiveCodingController::class, 'companyAcceptSchedule']);
    Route::patch('/{id}/cancel', [DevJobVacancyLiveCodingController::class, 'cancel']);

    // Call em si (sinalização WebRTC)
    Route::post('/{id}/join', [DevJobVacancyLiveCodingController::class, 'join']);
    Route::post('/{id}/leave', [DevJobVacancyLiveCodingController::class, 'leave']);
    Route::post('/{id}/signal', [DevJobVacancyLiveCodingController::class, 'signal']);
});
