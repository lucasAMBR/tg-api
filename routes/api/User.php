<?php

use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::patch("/{id}", [UserController::class, 'update'])->middleware('auth:api');
Route::delete('/{id}', [UserController::class, 'delete'])->middleware('auth:api');
Route::patch('/{id}/block', [UserController::class, 'blockUserAccess'])->middleware(['auth:api', 'can:user.block']);
Route::patch('/{id}/unblock', [UserController::class, 'unblockUserAccess'])->middleware(['auth:api', 'can:user.block']);
