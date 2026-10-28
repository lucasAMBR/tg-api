<?php

use App\Http\Controllers\LanguageAssessment\LanguageAssessmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    // Empresa monta e mantém a prova da vaga, com o gabarito
    Route::post('/vacancy/{job_vacancy_id}', [LanguageAssessmentController::class, 'store'])->middleware('can:company_job_vacancy.create');
    Route::get('/vacancy/{job_vacancy_id}', [LanguageAssessmentController::class, 'show'])->middleware('can:company_job_vacancy.view');
    Route::patch('/{id}', [LanguageAssessmentController::class, 'update'])->middleware('can:company_job_vacancy.update');
    Route::delete('/{id}', [LanguageAssessmentController::class, 'destroy'])->middleware('can:company_job_vacancy.delete');

    // Dev responde a prova que recebeu na etapa de avaliação de idioma
    Route::get('/response/{id}', [LanguageAssessmentController::class, 'showDevAssessment']);
    Route::patch('/response/{id}/answer', [LanguageAssessmentController::class, 'answer']);
});
