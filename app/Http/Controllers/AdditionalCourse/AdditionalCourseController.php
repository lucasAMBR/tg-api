<?php

namespace App\Http\Controllers\AdditionalCourse;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdditionalCourse\IndexAdditionalCourseRequest;
use App\Http\Requests\AdditionalCourse\StoreAdditionalCourseRequest;
use App\Http\Requests\AdditionalCourse\UpdateAdditionalCourseRequest;
use App\Models\AdditionalCourse;
use App\Services\AdditionalCourse\AdditionalCourseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class AdditionalCourseController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected AdditionalCourseService $additionalCourseService){}

    public function index(IndexAdditionalCourseRequest $request)
    {
        $additionalCourses = $this->additionalCourseService->index($request->validated());

        return ApiResponse::success($additionalCourses, 'Additional Courses Listed With Success!');
    }

    public function store(StoreAdditionalCourseRequest $request)
    {
        $additionalCourse = $this->additionalCourseService->store($request->validated());

        return ApiResponse::success($additionalCourse, 'Additional Course registered with success!', 201);
    }

    public function update(AdditionalCourse $additionalCourse, UpdateAdditionalCourseRequest $request)
    {
        $this->authorize('update', $additionalCourse);

        $additionalCourse = $this->additionalCourseService->update($additionalCourse, $request->validated());

        return ApiResponse::success($additionalCourse, 'Additional Course updated with success');
    }

    public function delete(AdditionalCourse $additionalCourse)
    {
        $this->authorize('delete', $additionalCourse);

        $this->additionalCourseService->delete($additionalCourse);

        return ApiResponse::success(message: 'Additional Course removed with success!');
    }
}
