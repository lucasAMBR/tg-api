<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/api/Auth.php'));

Route::prefix('profile')->group(base_path('routes/api/Profiles.php'));

Route::prefix('address')->group(base_path('routes/api/Address.php'));

Route::prefix('language')->group(base_path('routes/api/Languages.php'));

Route::prefix('employment-history')->group(base_path('routes/api/EmploymentHistory.php'));

