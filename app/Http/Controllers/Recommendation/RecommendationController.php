<?php

namespace App\Http\Controllers\Recommendation;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recommendation\RecommendationDevsRequest;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use App\Models\JobVacancy;
use App\Services\Recommendation\RecommendationService;

class RecommendationController extends Controller
{
    public function __construct(protected RecommendationService $recommendationService) {}

    public function devsForJobVacancy(RecommendationDevsRequest $request, JobVacancy $jobVacancy) {

        $devs = $this->recommendationService->recommendDevsForJobVacancy(
            $jobVacancy,
            $request->validated()
        );

        return ApiResponse::success(
            $devs->map(function($dev) {
                return [
                    'similarity' => round((float) $dev->similarity, 4),
                    'dev_profile' => new DevProfileResource($dev)
                ];
            }),
            'Recommended developers retrieved with success!',
            200
        );

    }
}
