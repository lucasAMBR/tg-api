<?php

use App\Http\Controllers\Languages\LanguageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LanguageController::class, 'index']);

Route::middleware('auth:api')->group(function() {
    Route::post('/', [LanguageController::class, 'store'])->middleware('can:language.create');
    Route::patch('/{language}', [LanguageController::class, 'update'])->middleware('can:language.update');
    Route::delete('/{language}', [LanguageController::class, 'delete'])->middleware('can:language.delete');

    Route::post('/approve/{language}', [LanguageController::class, 'approveLanguage'])->middleware('can:language.approve');
});
