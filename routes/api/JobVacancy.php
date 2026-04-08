<?php

use App\Http\Controllers\JobVacancy\JobVacancyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    Route::get('/', [JobVacancyController::class, 'index'])->middleware('can:company_job_vacancy.view');
    Route::post('/', [JobVacancyController::class, 'store'])->middleware('can:company_job_vacancy.create');
    Route::get('/{jobVacancy}', [JobVacancyController::class, 'show'])->middleware('can:company_job_vacancy.view');
    // Route::update('/{jobVacancy}', [JobVacancyController::class, 'update'])->middleware('can:company_job_vacancy.update');
    Route::delete('/{jobVacancy}', [JobVacancyController::class, 'destroy'])->middleware('can:company_job_vacancy.delete');
});
