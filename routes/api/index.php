<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/api/Auth.php'));
Route::prefix('post')->group(base_path('routes/api/Post.php'));
