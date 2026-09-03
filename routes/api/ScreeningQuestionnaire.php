<?php

use App\Http\Controllers\ScreeningQuestionnaire\ScreeningQuestionnaireController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    // Empresa monta e mantém o questionário da vaga
    Route::post('/vacancy/{job_vacancy_id}', [ScreeningQuestionnaireController::class, 'store'])->middleware('can:company_job_vacancy.create');
    Route::get('/vacancy/{job_vacancy_id}', [ScreeningQuestionnaireController::class, 'show'])->middleware('can:company_job_vacancy.view');
    Route::patch('/{id}', [ScreeningQuestionnaireController::class, 'update'])->middleware('can:company_job_vacancy.update');
    Route::delete('/{id}', [ScreeningQuestionnaireController::class, 'destroy'])->middleware('can:company_job_vacancy.delete');

    // Dev responde o questionário que recebeu na etapa de perguntas de triagem
    Route::get('/response/{id}', [ScreeningQuestionnaireController::class, 'showDevQuestionnaire']);
    Route::patch('/response/{id}/answer', [ScreeningQuestionnaireController::class, 'answer']);
});
