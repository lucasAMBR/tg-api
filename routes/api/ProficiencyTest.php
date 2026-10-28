<?php

use App\Http\Controllers\ProficiencyTest\ProficiencyTestController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/', [ProficiencyTestController::class, 'index'])
        ->middleware('can:proficiency_test.view');

    Route::get('/{id}', [ProficiencyTestController::class, 'show'])
        ->middleware('can:proficiency_test.view');

    Route::post('/{dev_profile_id}', [ProficiencyTestController::class, 'solicitateProficiencyTest'])
        ->middleware('can:proficiency_test.solicitate');

    Route::get('/{id}/questions', [ProficiencyTestController::class, 'getProficiencyTestQuestions'])
        ->middleware('can:proficiency_test.view');

    Route::get('/{id}/review', [ProficiencyTestController::class, 'getProficiencyTestReview'])
        ->middleware('can:proficiency_test.view');

    Route::post('/{id}/submit', [ProficiencyTestController::class, 'submitProficiencyTest'])
        ->middleware('can:proficiency_test.submit');

    Route::post('/{id}/visualizations', [ProficiencyTestController::class, 'registerVisualization'])
        ->middleware('can:proficiency_test.view');
});
