<?php

use App\Http\Controllers\FreelanceFeed\FreelanceFeedController;
use App\Http\Controllers\FreelanceInvitation\FreelanceInvitationController;
use App\Http\Controllers\FreelanceJobVacancy\FreelanceJobVacancyController;
use App\Http\Controllers\FreelanceProposal\FreelanceProposalController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    Route::get('/', [FreelanceJobVacancyController::class, 'index'])->middleware('can:client_job_vacancy.view');
    Route::post('/', [FreelanceJobVacancyController::class, 'store'])->middleware('can:client_job_vacancy.create');
    // Declaradas antes de /{id} para o caminho literal ter precedência.
    Route::get('/my-vacancies', [FreelanceJobVacancyController::class, 'indexMyVacancies'])->middleware('can:client_job_vacancy.view');
    Route::get('/my-contracts', [FreelanceJobVacancyController::class, 'indexMyContracts'])->middleware('can:client_job_vacancy.view');
    Route::get('/{id}', [FreelanceJobVacancyController::class, 'show'])->middleware('can:client_job_vacancy.view');
    Route::patch('/{id}', [FreelanceJobVacancyController::class, 'update'])->middleware('can:client_job_vacancy.update');
    Route::delete('/{id}', [FreelanceJobVacancyController::class, 'destroy'])->middleware('can:client_job_vacancy.delete');
    Route::patch('/{id}/complete', [FreelanceJobVacancyController::class, 'complete'])->middleware('can:client_job_vacancy.update');

    Route::get('/{id}/proposals', [FreelanceProposalController::class, 'index'])->middleware('can:freelance_proposal.view');
    Route::post('/{id}/proposals', [FreelanceProposalController::class, 'store'])->middleware('can:freelance_proposal.create');

    Route::post('/{id}/invitations', [FreelanceInvitationController::class, 'store'])->middleware('can:client_job_vacancy.update');

    Route::get('/{id}/feed', [FreelanceFeedController::class, 'index'])->middleware('can:freelance_feed.view');
    Route::post('/{id}/feed', [FreelanceFeedController::class, 'store'])->middleware('can:freelance_feed.create');
    Route::delete('/{id}/feed/{post_id}', [FreelanceFeedController::class, 'destroy'])->middleware('can:freelance_feed.delete');
});
