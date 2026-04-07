<?php

namespace App\Http\Controllers\JobVacancy;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\JobVacancy\IndexJobVacancyRequest;
use App\Http\Requests\JobVacancy\StoreJobVacancyRequest;
use App\Http\Resources\JobVacancy\JobVacancyCollection;
use App\Http\Resources\JobVacancy\JobVacancyResource;
use App\Services\JobVacancy\JobVacancyService;

class JobVacancyController extends Controller
{

    public function __construct(protected JobVacancyService $jobVacancy) {}

    public function index(IndexJobVacancyRequest $request) {

        $data = $this->jobVacancy->index($request->validated());
        return ApiResponse::success(
            JobVacancyResource::collection($data),
            'Job vacancies indexed with success!',
            200
        );
    }

    public function store(StoreJobVacancyRequest $request) {

        $data = $this->jobVacancy->store($request->validated());
        return ApiResponse::success(
            JobVacancyResource::collection($data),
            'Job vacancy created with success!',
            201
        );
    }

    public function show() {

    }

    public function update() {

    }

    public function destroy() {

    }

}
