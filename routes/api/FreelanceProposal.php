<?php

use App\Http\Controllers\FreelanceProposal\FreelanceProposalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    // Orçamentos do dev autenticado
    Route::get('/', [FreelanceProposalController::class, 'indexMine'])->middleware('can:freelance_proposal.view');
    Route::patch('/{id}', [FreelanceProposalController::class, 'update'])->middleware('can:freelance_proposal.update');
    Route::delete('/{id}', [FreelanceProposalController::class, 'destroy'])->middleware('can:freelance_proposal.delete');
    // Aceitar fecha o contrato do freela, por isso pede a permissão de gerenciar a vaga
    Route::patch('/{id}/accept', [FreelanceProposalController::class, 'accept'])->middleware('can:client_job_vacancy.update');
});
