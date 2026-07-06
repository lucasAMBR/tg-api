<?php

use App\Http\Controllers\Question\QuestionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/', [QuestionController::class, 'index'])->middleware('can:question.view');
    Route::post('/', [QuestionController::class, 'store'])->middleware('can:question.create');
    Route::get('/{question}', [QuestionController::class, 'show'])->middleware('can:question.view');
    Route::patch('/{question}', [QuestionController::class, 'update'])->middleware('can:question.update');
    Route::delete('/{question}', [QuestionController::class, 'deleteQuestion'])->middleware('can:question.delete');

    Route::post('/{question}/responses', [QuestionController::class, 'addResponse'])->middleware('can:question_response.create');
    Route::delete('/responses/{response}', [QuestionController::class, 'deleteResponse'])->middleware('can:question_response.delete');
});
