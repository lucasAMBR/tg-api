<?php

use App\Http\Controllers\Chat\ChatController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/', [ChatController::class, 'index']);
    Route::post('/', [ChatController::class, 'store']);
    Route::get('/{id}', [ChatController::class, 'show']);
    Route::patch('/{id}/read', [ChatController::class, 'markAsRead']);
    Route::get('/{id}/messages', [ChatController::class, 'indexMessages']);
    Route::post('/{id}/messages', [ChatController::class, 'storeMessage']);
});
