<?php

use App\Http\Controllers\DevJobVacancyInterview\DevJobVacancyInterviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    Route::patch('/{id}/set-schedule', [DevJobVacancyInterviewController::class, 'setInitialSchedule']);
    Route::patch('/{id}/company-propose-schedule', [DevJobVacancyInterviewController::class, 'companyProposeSchedule']);
    Route::patch('/{id}/dev-propose-schedule', [DevJobVacancyInterviewController::class, 'devProposeSchedule']);
    Route::patch('/{id}/dev-accept-schedule', [DevJobVacancyInterviewController::class, 'devAcceptSchedule']);
    Route::patch('/{id}/company-accept-schedule', [DevJobVacancyInterviewController::class, 'companyAcceptSchedule']);
    Route::patch('/{id}/cancel', [DevJobVacancyInterviewController::class, 'cancel']);

    // Call em si (sinalização WebRTC)
    Route::post('/{id}/join', [DevJobVacancyInterviewController::class, 'join']);
    Route::post('/{id}/leave', [DevJobVacancyInterviewController::class, 'leave']);
    Route::post('/{id}/signal', [DevJobVacancyInterviewController::class, 'signal']);
});
