<?php

use App\Http\Controllers\Enums\EnumController;
use Illuminate\Support\Facades\Route;

Route::get('/seniority', [EnumController::class, 'listSeniorityLevelEnumCases']);
Route::get('/hard-skill-level', [EnumController::class, 'listHardSkillLevelEnumCases']);
Route::get('/contract-type', [EnumController::class, 'listContractType']);
Route::get('/employment-type', [EnumController::class, 'listEmploymentType']);
Route::get("/degree-level", [EnumController::class, 'listDegreeLevels']);
Route::get("/operational-segment", [EnumController::class, 'listOperationalSegments']);
Route::get("/dev-specialty", [EnumController::class, 'listDevSpecialties']);
