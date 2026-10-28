<?php

use App\Http\Controllers\Question\QuestionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/', [QuestionController::class, 'index'])->middleware('can:question.view');
    Route::post('/', [QuestionController::class, 'store'])->middleware('can:question.create');
    Route::get('/{id}', [QuestionController::class, 'show'])->middleware('can:question.view');
    Route::patch('/{id}', [QuestionController::class, 'update'])->middleware('can:question.update');
    Route::delete('/{id}', [QuestionController::class, 'deleteQuestion'])->middleware('can:question.delete');

    Route::post('/{question_id}/responses', [QuestionController::class, 'addResponse'])->middleware('can:question_response.create');
    Route::delete('/responses/{id}', [QuestionController::class, 'deleteResponse'])->middleware('can:question_response.delete');
});
