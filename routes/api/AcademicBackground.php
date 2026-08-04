<?php

use App\Http\Controllers\AcademicBackground\AcademicBackgroundController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/', [AcademicBackgroundController::class, 'index']);
    Route::post('/', [AcademicBackgroundController::class, 'store'])->middleware('can:academic_background.create');
    Route::post('/{id}', [AcademicBackgroundController::class, 'update'])->middleware('can:academic_background.update');
    Route::delete('/{id}', [AcademicBackgroundController::class, 'delete'])->middleware('can:academic_background.delete');
});
