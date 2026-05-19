<?php

use App\Http\Controllers\SoftSkill\SoftSkillController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get("/", [SoftSkillController::class, 'index']);
    Route::get("/dev/{profile}", [SoftSkillController::class, "listDevSoftSkill"]);
    Route::post("/dev", [SoftSkillController::class, 'storeDevSoftSkills'])->middleware('can:dev_soft_skill.create');
    Route::put("/dev", [SoftSkillController::class, 'updateDevSoftSkills'])->middleware('can:dev_soft_skill.update');

    Route::get('/company/{company}', [SoftSkillController::class, 'indexCompanySoftSkills'])->middleware('can:company_soft_skill.view');
    Route::post('/company/sync/{company}', [SoftSkillController::class, 'syncCompanySoftSkills'])->middleware('can:company_soft_skill.sync');
});
