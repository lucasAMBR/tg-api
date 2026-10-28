<?php

use App\Http\Controllers\Like\LikeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::post('/post/{id}/toggle', [LikeController::class, 'togglePost'])->middleware('can:post.view');
    Route::get('/post/{id}', [LikeController::class, 'indexPost'])->middleware('can:post.view');

    Route::post('/comment/{id}/toggle', [LikeController::class, 'toggleComment'])->middleware('can:comment.view');
    Route::get('/comment/{id}', [LikeController::class, 'indexComment'])->middleware('can:comment.view');
});
