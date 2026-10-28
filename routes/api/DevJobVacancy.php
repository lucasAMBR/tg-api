<?php 

use App\Http\Controllers\DevJobVacancy\DevJobVacancyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    Route::get('/applies', [DevJobVacancyController::class, 'indexApplies']);
    Route::get('/my-applies', [DevJobVacancyController::class, 'indexMyApplies']);
    Route::get('/{job_vacancy_id}/step-applies', [DevJobVacancyController::class, 'indexStepApplies']);
    Route::get('/{job_vacancy_id}/step-results', [DevJobVacancyController::class, 'stepResults']);
    Route::post('/{job_vacancy_id}/apply', [DevJobVacancyController::class, 'apply']);
    Route::patch('/{job_vacancy_id}/advance-step', [DevJobVacancyController::class, 'advanceStep']);
    Route::patch('/{id}', [DevJobVacancyController::class, 'reviewApply']);
});
