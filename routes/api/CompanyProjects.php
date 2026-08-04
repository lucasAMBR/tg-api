<?php

use App\Http\Controllers\CompanyProjects\CompanyProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    Route::get('/', [CompanyProjectController::class, 'index']);
    Route::post('/', [CompanyProjectController::class, 'store'])->middleware('can:company_project.create'); 
    Route::patch('/{id}', [CompanyProjectController::class, 'update'])->middleware('can:company_project.update');
    Route::delete('/{id}', [CompanyProjectController::class, 'destroy'])->middleware('can:company_project.delete');
        
});