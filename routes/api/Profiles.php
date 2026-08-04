<?php

use App\Http\Controllers\Profiles\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::prefix('/dev')->group(function () {
        Route::get('/', [ProfileController::class, 'indexDevProfiles'])->middleware('can:dev_profile.view');
        Route::get('/{id}', [ProfileController::class, 'showDevProfile'])->middleware('can:dev_profile.view');
        Route::post('/', [ProfileController::class, 'storeDevProfile'])->middleware('can:dev_profile.create');
        Route::patch('/{id}', [ProfileController::class, 'updateDevProfile'])->middleware('can:dev_profile.update');
        Route::delete('/{id}', [ProfileController::class, 'destroyDevProfile'])->middleware('can:dev_profile.delete');
    });

    Route::prefix('/company')->group(function () {
        Route::get('/', [ProfileController::class, 'indexCompanyProfiles'])->middleware('can:company_profile.view');
        Route::get('/stack/{id}', [ProfileController::class, 'getCompanyStack']);
        Route::post('/stack/sync/{id}', [ProfileController::class, 'syncCompanyStack'])->middleware('can:company_stack.sync');
        Route::get('/{id}', [ProfileController::class, 'showCompanyProfile'])->middleware('can:company_profile.view');
        Route::post('/', [ProfileController::class, 'storeCompanyProfile'])->middleware('can:company_profile.create');
        Route::patch('/{id}', [ProfileController::class, 'updateCompanyProfile'])->middleware('can:company_profile.update');
        Route::delete('/{id}', [ProfileController::class, 'destroyCompanyProfile'])->middleware('can:company_profile.delete');
    });

    Route::prefix('/client')->group(function () {
        Route::get('/', [ProfileController::class, 'indexClientProfiles'])->middleware('can:client_profile.view');
        Route::get('/{id}', [ProfileController::class, 'showClientProfile'])->middleware('can:client_profile.view');
        Route::post('/', [ProfileController::class, 'storeClientProfile'])->middleware('can:client_profile.create');
        Route::patch('/{id}', [ProfileController::class, 'updateClientProfile'])->middleware('can:client_profile.update');
        Route::delete('/{id}', [ProfileController::class, 'destroyClientProfile'])->middleware('can:client_profile.delete');
    });
});
