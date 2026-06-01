<?php

namespace App\Http\Controllers\DevJobVacancy;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\DevJobVacancy\IndexDevJobVacancyRequest;
use App\Http\Requests\DevJobVacancy\ReviewDevJobVacancyRequest;
use App\Http\Requests\DevJobVacancy\StoreDevJobVacancyRequest;
use App\Http\Resources\DevJobVacancy\DevJobVacancyCollection;
use App\Http\Resources\DevJobVacancy\DevJobVacancyResource;
use App\Services\DevJobVacancy\DevJobVacancyService;
use Illuminate\Http\Request;

class DevJobVacancyController extends Controller
{
    public function __construct(protected DevJobVacancyService $devJobVacancyService) {}

    public function apply(StoreDevJobVacancyRequest $request) {
        
        $data = $this->devJobVacancyService->apply($request->validated());
        
        return ApiResponse::success(
            new DevJobVacancyResource($data),
            'User registered for the vacancy with success!',
            201
        );

    }

    public function indexApplies(IndexDevJobVacancyRequest $request) {
    
        $data = $this->devJobVacancyService->indexApplies($request->validated());
        
        return ApiResponse::success(
            new DevJobVacancyCollection($data),
            'Applies indexed with success!',
            200
        );

    }

    public function reviewApply(ReviewDevJobVacancyRequest $request) {
    
        $data = $this->devJobVacancyService->reviewApply($request->validated());
        
        return ApiResponse::success(
            $data,
            'Apply rate with success!',
            200
        );

    }
        
}
