<?php

use App\Http\Controllers\ProjectHistory\ProjectHistoryController;
use App\Models\ProjectHistory;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/', [ProjectHistoryController::class, 'index']);
    Route::post('/', [ProjectHistoryController::class, 'store'])->middleware('can:project_history.create');
    Route::patch('/{projectHistory}', [ProjectHistoryController::class, 'update'])->middleware('can:project_history.update');
    Route::delete('/{projectHistory}', [ProjectHistoryController::class, 'delete'])->middleware('can:project_history.delete');

    Route::post('/{projectHistory}/gallery', [ProjectHistoryController::class, 'saveImagesInProject'])->middleware('can:project_history.update');
    Route::delete('/{projectHistory}/gallery/remove/{imageId}', [ProjectHistoryController::class, 'removeImageFromProject'])->middleware('can:project_history.update');
});
