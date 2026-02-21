<?php

use App\Http\Controllers\Profiles\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::prefix('/dev')->group(function () {
        Route::post('/', [ProfileController::class, 'storeDevProfile'])->middleware('can:dev_profile.create');
        Route::patch('/{dev}', [ProfileController::class, 'updateDevProfile'])->middleware('can:dev_profile.update');
        Route::delete('/{dev}', [ProfileController::class, 'destroyDevProfile'])->middleware('can:dev_profile.delete');
    });

    Route::prefix('/company')->group(function () {
        Route::post('/', [ProfileController::class, 'storeCompanyProfile'])->middleware('can:company_profile.create');
        Route::patch('/{company}', [ProfileController::class, 'updateCompanyProfile'])->middleware('can:company_profile.update');
        Route::delete('/{company}', [ProfileController::class, 'destroyCompanyProfile'])->middleware('can:company_profile.delete');
    });

    Route::prefix('/client')->group(function () {
        Route::post('/', [ProfileController::class, 'storeClientProfile'])->middleware('can:client_profile.create');
        Route::patch('/{client}', [ProfileController::class, 'updateClientProfile'])->middleware('can:client_profile.update');
        Route::delete('/{client}', [ProfileController::class, 'destroyClientProfile'])->middleware('can:client_profile.delete');
    });
});
