<?php

use App\Http\Controllers\EmploymentHistory\EmploymentHistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/', [EmploymentHistoryController::class, 'index']);
    Route::post('/', [EmploymentHistoryController::class, 'store'])->middleware('can:employment_history.create');
    Route::patch('/{id}', [EmploymentHistoryController::class, 'update'])->middleware('can:employment_history.update');
    Route::delete('/{id}', [EmploymentHistoryController::class, 'delete'])->middleware('can:employment_history.delete');
});
