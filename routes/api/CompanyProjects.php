<?php

use App\Http\Controllers\CompanyProjects\CompanyProjectController;
use Illuminate\Support\Facades\Route;

// Route::middleware('auth:api')->group(function() {
    // Route::get('/', [CompanyProjectsController::class, 'index']);
Route::post('/', [CompanyProjectController::class, 'store'])->middleware('can:company_project.create'); // Arrumar depois com as permissões
    // Route::update('/{companyProject}', [CompanyProjectsController::class, 'update'])->middleware('can:company_portfolio.update');
    // Route::delete('/{companyProject}', [CompanyProjectsController::class, 'destroy'])->middleware('can:company_portfolio.delete');
        
// });