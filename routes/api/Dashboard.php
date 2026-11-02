<?php

use App\Http\Controllers\Dashboard\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/company', [DashboardController::class, 'company'])->middleware('can:company.dashboard');
    Route::get('/admin', [DashboardController::class, 'admin'])->middleware('can:administration.dashboard');
});
