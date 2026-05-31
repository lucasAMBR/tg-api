<?php 

use App\Http\Controllers\DevJobVacancy\DevJobVacancyController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->group(function() {
    Route::post('/{jobVacancyId}/apply', [DevJobVacancyController::class, 'apply']);
});