<?php

use App\Http\Controllers\Embedding\EmbeddingController;
use Illuminate\Support\Facades\Route;

Route::post('generate', [EmbeddingController::class, 'generate']);
