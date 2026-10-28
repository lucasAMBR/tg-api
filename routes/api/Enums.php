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
Route::get("/freelance-job-type", [EnumController::class, 'listFreelanceJobType']);
Route::get("/freelance-job-vacancy-status", [EnumController::class, 'listFreelanceJobVacancyStatus']);
Route::get("/freelance-proposal-status", [EnumController::class, 'listFreelanceProposalStatus']);
Route::get("/freelance-proposal-pricing-type", [EnumController::class, 'listFreelanceProposalPricingType']);
Route::get("/freelance-feed-post-type", [EnumController::class, 'listFreelanceFeedPostType']);
Route::get("/freelance-review-status", [EnumController::class, 'listFreelanceReviewStatus']);
Route::get("/screening-question-type", [EnumController::class, 'listScreeningQuestionTypes']);

Route::middleware('auth:api')->group(function () {
    Route::get("/question-category-stacks", [EnumController::class, 'listQuestionCategoryStacks']);
});
