<?php

use App\Http\Controllers\HardSkill\HardSkillController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/', [HardSkillController::class, 'index']);
    Route::post('/', [HardSkillController::class, 'store'])->middleware('can:hard_skill.create');
    Route::get("/{id}", [HardSkillController::class, 'show']);
    Route::patch('/{id}', [HardSkillController::class, 'update'])->middleware('can:hard_skill.update');
    Route::delete('/{id}', [HardSkillController::class, 'delete'])->middleware('can:hard_skill.delete');
});
