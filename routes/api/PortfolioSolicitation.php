<?php

use App\Http\Controllers\PortfolioSolicitation\PortfolioSolicitationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    Route::patch('/{id}', [PortfolioSolicitationController::class, 'update']);
});
