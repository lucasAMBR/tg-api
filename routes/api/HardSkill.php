<?php

use App\Http\Controllers\HardSkill\HardSkillController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/', [HardSkillController::class, 'index']);
    Route::post('/', [HardSkillController::class, 'store'])->middleware('can:hard_skill.create');
    Route::patch('/{hardSkill}', [HardSkillController::class, 'update'])->middleware('can:hard_skill.update');
    Route::delete('/{hardSkill}', [HardSkillController::class, 'delete'])->middleware('can:hard_skill.delete');
});
