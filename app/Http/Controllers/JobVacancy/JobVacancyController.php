<?php

namespace App\Http\Controllers\JobVacancy;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\JobVacancy\IndexJobVacancyRequest;
use App\Http\Requests\JobVacancy\StoreJobVacancyRequest;
use App\Http\Requests\JobVacancy\UpdateJobVacancyRequest;
use App\Http\Resources\JobVacancy\JobVacancyCollection;
use App\Http\Resources\JobVacancy\JobVacancyResource;
use App\Models\JobVacancy;
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
            new JobVacancyResource($data),
            'Job vacancy created with success!',
            201
        );
    }

    public function show(JobVacancy $jobVacancy) {

        $data = $this->jobVacancy->show($jobVacancy);
        return ApiResponse::success(
            new JobVacancyResource($data),
            'Job vacancy indexed with success!',
            200
        );
    }

    public function update(UpdateJobVacancyRequest $request, JobVacancy $jobVacancy) {

        $data = $this->jobVacancy->update($request->validated(), $jobVacancy);
        return ApiResponse::success(
            new JobVacancyResource($data),
            'Job vacancy updated with success!',
            200
        );

    }

    public function destroy(JobVacancy $jobVacancy) {

        $this->jobVacancy->destroy($jobVacancy);
        return ApiResponse::success(
            null,
            'Job vacancy deleted with success!',
            200
        );
    }

}
