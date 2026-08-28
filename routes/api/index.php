<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/api/Auth.php'));

Route::prefix('profile')->group(base_path('routes/api/Profiles.php'));

Route::prefix('address')->group(base_path('routes/api/Address.php'));

Route::prefix('language')->group(base_path('routes/api/Languages.php'));

Route::prefix('employment-history')->group(base_path('routes/api/EmploymentHistory.php'));

Route::prefix('project-history')->group(base_path('routes/api/ProjectHistory.php'));

Route::prefix('company-project')->group(base_path('routes/api/CompanyProjects.php'));

Route::prefix('academic-background')->group(base_path('routes/api/AcademicBackground.php'));

Route::prefix('additional-course')->group(base_path('routes/api/AdditionalCourse.php'));

Route::prefix('hard-skill')->group(base_path('routes/api/HardSkill.php'));

Route::prefix('soft-skill')->group(base_path('routes/api/SoftSkill.php'));

Route::prefix('enum')->group(base_path('routes/api/Enums.php'));

Route::prefix('job-vacancy')->group(base_path('routes/api/JobVacancy.php'));

Route::prefix('freelance-job-vacancy')->group(base_path('routes/api/FreelanceJobVacancy.php'));

Route::prefix('user')->group(base_path('routes/api/User.php'));

Route::prefix('recommendation-preferences')->group(base_path('routes/api/RecommendationPreferences.php'));

Route::prefix('dev-vacancy')->group(base_path('routes/api/DevJobVacancy.php'));

Route::prefix('portfolio-solicitation')->group(base_path('routes/api/PortfolioSolicitation.php'));

Route::prefix('question')->group(base_path('routes/api/Question.php'));

Route::prefix('proficiency-test')->group(base_path('routes/api/ProficiencyTest.php'));

Route::prefix('notification')->group(base_path('routes/api/Notification.php'));

Route::prefix('search')->group(base_path('routes/api/Search.php'));

Route::prefix('recommendation')->group(base_path('routes/api/Recommendation.php'));

Broadcast::routes(['middleware' => ['auth:api']]);