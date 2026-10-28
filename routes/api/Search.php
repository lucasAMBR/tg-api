<?php

use App\Http\Controllers\Search\SearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    Route::get('/', [SearchController::class, 'search']);
    Route::get('/top-companies', [SearchController::class, 'topCompanies']);
    Route::get('/top-devs', [SearchController::class, 'topDevs']);
    Route::get('/top-clients', [SearchController::class, 'topClients']);
    Route::get('/top-job-vacancies', [SearchController::class, 'topJobVacancies']);
});
