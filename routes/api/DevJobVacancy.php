<?php 

use App\Http\Controllers\DevJobVacancy\DevJobVacancyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    Route::get('/applies', [DevJobVacancyController::class, 'indexApplies']);
    Route::post('/{job_vacancy_id}/apply', [DevJobVacancyController::class, 'apply']);
    Route::patch('/{id}', [DevJobVacancyController::class, 'reviewApply']);
});