<?php

use App\Http\Controllers\Post\PostController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/', [PostController::class, 'index'])->middleware('can:post.view');
    Route::post('/', [PostController::class, 'store'])->middleware('can:post.create');
    // Declarada antes de /{id} para o caminho literal ter precedência.
    Route::get('/favorites', [PostController::class, 'indexFavorites'])->middleware('can:post.view');
    Route::get('/{id}', [PostController::class, 'show'])->middleware('can:post.view');
    Route::patch('/{id}', [PostController::class, 'update'])->middleware('can:post.update');
    Route::delete('/{id}', [PostController::class, 'delete'])->middleware('can:post.delete');

    Route::post('/{id}/favorite', [PostController::class, 'toggleFavorite'])->middleware('can:post.view');

    // Sai dos comentários, por isso pede a mesma permissão de ver comentários.
    Route::get('/{id}/participants', [PostController::class, 'participants'])->middleware('can:comment.view');

    Route::post('/{id}/images', [PostController::class, 'saveImages'])->middleware('can:post.update');
    Route::delete('/{id}/images/{image_id}', [PostController::class, 'removeImage'])->middleware('can:post.update');
});
