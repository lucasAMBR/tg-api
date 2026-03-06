<?php

use App\Http\Controllers\AdditionalCourse\AdditionalCourseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function () {
    Route::get('/', [AdditionalCourseController::class, 'index']);
    Route::post('/', [AdditionalCourseController::class, 'store'])->middleware('can:additional_course.create');
    Route::post('/{additionalCourse}', [AdditionalCourseController::class, 'update'])->middleware('can:additional_course.update');
    Route::delete('/{additionalCourse}', [AdditionalCourseController::class, 'delete'])->middleware('can:additional_course.delete');
});
