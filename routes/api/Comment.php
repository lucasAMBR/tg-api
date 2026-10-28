<?php

use App\Http\Controllers\Comment\CommentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/', [CommentController::class, 'index'])->middleware('can:comment.view');
    Route::post('/', [CommentController::class, 'store'])->middleware('can:comment.create');
    Route::get('/{id}/replies', [CommentController::class, 'replies'])->middleware('can:comment.view');
    Route::get('/{id}', [CommentController::class, 'show'])->middleware('can:comment.view');
    Route::patch('/{id}', [CommentController::class, 'update'])->middleware('can:comment.update');
    Route::delete('/{id}', [CommentController::class, 'delete'])->middleware('can:comment.delete');
});
