<?php

use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

// Declarada antes das rotas com /{id} para o caminho literal ter precedência.
Route::get('/popular', [UserController::class, 'indexPopular'])->middleware(['auth:api', 'can:follow.view']);
Route::patch("/{id}", [UserController::class, 'update'])->middleware('auth:api');
// O PHP não faz parse de multipart/form-data em PATCH/PUT, então o upload de
// profile_pic precisa desta variante POST.
Route::post("/{id}", [UserController::class, 'update'])->middleware('auth:api');
Route::delete('/{id}', [UserController::class, 'delete'])->middleware('auth:api');
Route::patch('/{id}/block', [UserController::class, 'blockUserAccess'])->middleware(['auth:api', 'can:user.block']);
Route::patch('/{id}/unblock', [UserController::class, 'unblockUserAccess'])->middleware(['auth:api', 'can:user.block']);
