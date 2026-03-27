<?php

use App\Http\Controllers\SoftSkill\SoftSkillController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get("/", [SoftSkillController::class, 'index']);
    Route::post("/dev", [SoftSkillController::class, 'storeDevSoftSkills'])->middleware('can:dev_soft_skill.create');
    Route::put("/dev", [SoftSkillController::class, 'updateDevSoftSkills'])->middleware('can:dev_soft_skill.update');
    
    Route::post("/company", [SoftSkillController::class, 'storeCompanySoftSkills']);
    Route::put("/company", [SoftSkillController::class, 'updateCompanySoftSkills']);
});
