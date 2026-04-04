<?php

use App\Http\Controllers\JobVacancy\JobVacancyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    Route::post('/', [JobVacancyController::class, 'store'])->middleware('can:company_job_vacancy.create');
});
