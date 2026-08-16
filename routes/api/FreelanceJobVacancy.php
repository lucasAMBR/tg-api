<?php

use App\Http\Controllers\FreelanceJobVacancy\FreelanceJobVacancyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    Route::get('/', [FreelanceJobVacancyController::class, 'index'])->middleware('can:client_job_vacancy.view');
    Route::post('/', [FreelanceJobVacancyController::class, 'store'])->middleware('can:client_job_vacancy.create');
    Route::get('/{id}', [FreelanceJobVacancyController::class, 'show'])->middleware('can:client_job_vacancy.view');
    Route::patch('/{id}', [FreelanceJobVacancyController::class, 'update'])->middleware('can:client_job_vacancy.update');
    Route::delete('/{id}', [FreelanceJobVacancyController::class, 'destroy'])->middleware('can:client_job_vacancy.delete');
});
