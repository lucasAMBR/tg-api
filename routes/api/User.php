<?php

use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::patch("/{user}", [UserController::class, 'update'])->middleware('auth:api');
Route::delete('/{user}', [UserController::class, 'delete'])->middleware('auth:api');
Route::patch('/{user}/block', [UserController::class, 'blockUserAccess'])->middleware(['auth:api', 'can:user.block']);
Route::patch('/{user}/unblock', [UserController::class, 'unblockUserAccess'])->middleware(['auth:api', 'can:user.block']);
