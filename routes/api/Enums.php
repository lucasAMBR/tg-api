<?php

use App\Http\Controllers\Enums\EnumController;
use Illuminate\Support\Facades\Route;

Route::get('/seniority', [EnumController::class, 'listSeniorityLevelEnumCases']);
