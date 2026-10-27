<?php

use App\Http\Controllers\Follow\FollowController;
use Illuminate\Support\Facades\Route;

// {id} é o id do usuário: seguir é uma relação entre usuários, qualquer que seja o perfil.
Route::middleware('auth:api')->group(function () {
    Route::post('/{id}/toggle', [FollowController::class, 'toggle'])->middleware('can:follow.toggle');
    Route::get('/{id}/followers', [FollowController::class, 'indexFollowers'])->middleware('can:follow.view');
    Route::get('/{id}/following', [FollowController::class, 'indexFollowing'])->middleware('can:follow.view');
});
