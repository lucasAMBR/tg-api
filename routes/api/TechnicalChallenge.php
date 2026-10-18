<?php

use App\Http\Controllers\TechnicalChallenge\TechnicalChallengeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    // Empresa monta e mantém o desafio técnico da vaga
    Route::post('/vacancy/{job_vacancy_id}', [TechnicalChallengeController::class, 'store'])->middleware('can:company_job_vacancy.create');
    Route::get('/vacancy/{job_vacancy_id}', [TechnicalChallengeController::class, 'show'])->middleware('can:company_job_vacancy.view');
    Route::patch('/{id}', [TechnicalChallengeController::class, 'update'])->middleware('can:company_job_vacancy.update');
    Route::delete('/{id}', [TechnicalChallengeController::class, 'destroy'])->middleware('can:company_job_vacancy.delete');

    // Documento guia do desafio, em endpoints próprios por ser upload de arquivo
    Route::post('/{id}/guide-document', [TechnicalChallengeController::class, 'saveGuideDocument'])->middleware('can:company_job_vacancy.update');
    Route::delete('/{id}/guide-document', [TechnicalChallengeController::class, 'removeGuideDocument'])->middleware('can:company_job_vacancy.update');

    // Dev entrega o desafio que recebeu na etapa de desafio técnico
    Route::get('/submission/{id}', [TechnicalChallengeController::class, 'showDevChallenge']);
    Route::post('/submission/{id}/submit', [TechnicalChallengeController::class, 'submit']);
});
