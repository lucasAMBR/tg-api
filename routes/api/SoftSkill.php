<?php

use App\Http\Controllers\SoftSkill\SoftSkillController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get("/", [SoftSkillController::class, 'index']);
    Route::post("/dev", [SoftSkillController::class, 'storeDevSoftSkills'])->middleware('can:dev_soft_skill.create');
    Route::put("/dev", [SoftSkillController::class, 'updateDevSoftSkills'])->middleware('can:dev_soft_skill.update');

    Route::post("/company", [SoftSkillController::class, 'storeCompanySoftSkills']);
    Route::patch("/company/{companySoft}", [SoftSkillController::class, 'updateCompanySoftSkills']);
    Route::delete("/company/{companySoft}", [SoftSkillController::class, 'destroyCompanySoftSkills']);
    Route::get('/company', [SoftSkillController::class, 'indexCompanySoftSkills']);
});
