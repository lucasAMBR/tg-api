<?php

use App\Http\Controllers\ProjectHistory\ProjectHistoryController;
use App\Models\ProjectHistory;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::post('/', [ProjectHistoryController::class, 'store'])->middleware('can:project_history.create');
    Route::post('/{projectHistory}/gallery', [ProjectHistoryController::class, 'saveImagesInProject'])->middleware('can:project_history.update');
});
