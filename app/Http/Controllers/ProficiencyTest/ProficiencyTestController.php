<?php

namespace App\Http\Controllers\ProficiencyTest;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProficiencyTest\IndexProficiencyTest;
use App\Http\Requests\ProficiencyTest\SolicitateProficiencyTestRequest;
use App\Http\Requests\ProficiencyTest\StoreProficiencyTestVisualizationRequest;
use App\Http\Requests\ProficiencyTest\SubmitProficiencyTestRequest;
use App\Http\Resources\ProficiencyTest\ProficiencyTestResource;
use App\Models\DevProfile;
use App\Models\ProficiencyTest;
use App\Services\ProficiencyTest\ProficiencyTestService;

class ProficiencyTestController extends Controller
{
    public function __construct(private ProficiencyTestService $proficiencyTestService){}

    public function solicitateProficiencyTest(DevProfile $devProfile, SolicitateProficiencyTestRequest $request)
    {
        $ProficiencyTest = $this->proficiencyTestService->solicitateProficiencyTest($devProfile, $request->validated());

        return ApiResponse::success($ProficiencyTest, 'Proficiency test solicitated with success', 200);
    }

    public function getProficiencyTestQuestions(ProficiencyTest $proficiencyTest)
    {
        $questions = $this->proficiencyTestService->getProficiencyTestQuestions($proficiencyTest);

        return ApiResponse::success($questions, 'Proficiency test questions retrieved with success', 200);
    }

    public function submitProficiencyTest(ProficiencyTest $proficiencyTest, SubmitProficiencyTestRequest $request)
    {
        $result = $this->proficiencyTestService->submitProficiencyTest($proficiencyTest, $request->validated()['chunks']);

        return ApiResponse::success($result, 'Proficiency test submitted with success', 200);
    }

    public function index(IndexProficiencyTest $request)
    {
        $proficiencyTests = $this->proficiencyTestService->index($request->validated());

        return ApiResponse::success($proficiencyTests, 'Proficiency tests listed with success', 200);
    }

    public function registerVisualization(ProficiencyTest $proficiencyTest, StoreProficiencyTestVisualizationRequest $request)
    {
        $visualization = $this->proficiencyTestService->registerVisualization($proficiencyTest, $request->validated());

        return ApiResponse::success($visualization, 'Proficiency test visualization registered with success', 201);
    }

    public function getProficiencyTestReview(ProficiencyTest $proficiencyTest)
    {
        $review = $this->proficiencyTestService->getProficiencyTestReview($proficiencyTest);

        return ApiResponse::success($review, 'Proficiency test review retrieved with success', 200);
    }

    public function show(ProficiencyTest $proficiencyTest)
    {
        return ApiResponse::success(new ProficiencyTestResource($proficiencyTest), 'Proficiency test retrieved with success', 200);
    }
}
