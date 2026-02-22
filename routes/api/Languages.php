<?php

use App\Http\Controllers\Languages\LanguageController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    Route::post('/', [LanguageController::class, 'store'])->middleware('can:language.create');
});