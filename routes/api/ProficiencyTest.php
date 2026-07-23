<?php

use App\Http\Controllers\ProficiencyTest\ProficiencyTestController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/', [ProficiencyTestController::class, 'index'])
        ->middleware('can:proficiency_test.view');

    Route::get('/{proficiencyTest}', [ProficiencyTestController::class, 'show'])
        ->middleware('can:proficiency_test.view');

    Route::post('/{devProfile}', [ProficiencyTestController::class, 'solicitateProficiencyTest'])
        ->middleware('can:proficiency_test.solicitate');

    Route::get('/{proficiencyTest}/questions', [ProficiencyTestController::class, 'getProficiencyTestQuestions'])
        ->middleware('can:proficiency_test.view');

    Route::post('/{proficiencyTest}/submit', [ProficiencyTestController::class, 'submitProficiencyTest'])
        ->middleware('can:proficiency_test.submit');

    Route::post('/{proficiencyTest}/visualizations', [ProficiencyTestController::class, 'registerVisualization'])
        ->middleware('can:proficiency_test.view');
});
