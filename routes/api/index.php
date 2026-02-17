<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/api/Auth.php'));

Route::prefix('profile')->group(base_path('routes/api/Profiles.php'));
