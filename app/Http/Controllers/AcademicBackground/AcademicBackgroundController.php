<?php

namespace App\Http\Controllers\AcademicBackground;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcademicBackground\IndexAcademicBackgroundRequest;
use App\Http\Requests\AcademicBackground\StoreAcademicBackgroundRequest;
use App\Http\Requests\AcademicBackground\UpdateAcademicBackgroundRequest;
use App\Models\AcademicBackground;
use App\Services\AcademicBackground\AcademicBackgroundService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class AcademicBackgroundController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected AcademicBackgroundService $academicBackgroundService
    ){}

    public function index(IndexAcademicBackgroundRequest $request)
    {
        $academicBackgrounds = $this->academicBackgroundService->index($request->validated());

        return ApiResponse::success($academicBackgrounds, "Academic backgrounds listed with success!");
    }

    public function store(StoreAcademicBackgroundRequest $request)
    {
        $academicBackground = $this->academicBackgroundService->store($request->validated());

        return ApiResponse::success($academicBackground, "Academic background registered with success!", 201);
    }

    public function update(AcademicBackground $academicBackground, UpdateAcademicBackgroundRequest $request)
    {
        $this->authorize('update', $academicBackground);

        $academicBackground = $this->academicBackgroundService->update($academicBackground, $request->validated());

        return ApiResponse::success($academicBackground, "Academic background updated with success!");
    }

    public function delete(AcademicBackground $academicBackground)
    {
        $this->authorize('delete', $academicBackground);

        $this->academicBackgroundService->delete($academicBackground);

        return ApiResponse::success(message: "Academic background removed with success!");
    }
}
