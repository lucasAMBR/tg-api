<?php

use App\Http\Controllers\ProjectHistory\ProjectHistoryController;
use App\Models\ProjectHistory;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/', [ProjectHistoryController::class, 'index']);
    Route::post('/', [ProjectHistoryController::class, 'store'])->middleware('can:project_history.create');
    Route::get("/{id}", [ProjectHistoryController::class, 'show']);
    Route::patch('/{id}', [ProjectHistoryController::class, 'update'])->middleware('can:project_history.update');
    Route::delete('/{id}', [ProjectHistoryController::class, 'delete'])->middleware('can:project_history.delete');

    Route::post('/{id}/gallery', [ProjectHistoryController::class, 'saveImagesInProject'])->middleware('can:project_history.update');
    Route::delete('/{id}/gallery/remove/{image_id}', [ProjectHistoryController::class, 'removeImageFromProject'])->middleware('can:project_history.update');
});
