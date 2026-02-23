<?php

namespace App\Http\Controllers\EmploymentHistory;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\EmploymentHistory\IndexEmploymentHistoryRequest;
use App\Http\Requests\EmploymentHistory\StoreEmploymentHistoryRequest;
use App\Http\Requests\EmploymentHistory\UpdateEmploymentHistoryRequest;
use App\Models\EmploymentHistory;
use App\Services\EmploymentHistory\EmploymentHistoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EmploymentHistoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected EmploymentHistoryService $employmentHistoryService){}

    public function store(StoreEmploymentHistoryRequest $request)
    {
        $employmentHistory = $this->employmentHistoryService->store($request->validated());

        return ApiResponse::success($employmentHistory, "Employment History item created with sucess!", 201);
    }

    public function update(EmploymentHistory $employmentHistory, UpdateEmploymentHistoryRequest $request)
    {
        $this->authorize('update', $employmentHistory);

        $employmentHistory = $this->employmentHistoryService->update($employmentHistory, $request->validated());

        return ApiResponse::success($employmentHistory, "Employment History item updated with success!", 200);
    }

    public function delete(EmploymentHistory $employmentHistory)
    {
        $this->authorize('delete', $employmentHistory);

        $employmentHistory = $this->employmentHistoryService->delete($employmentHistory);

        return ApiResponse::success($employmentHistory, "Employment History item updated with success!", 200);
    }

    public function index(IndexEmploymentHistoryRequest $request)
    {
        $employmentHistory = $this->employmentHistoryService->index($request->validated());

        return ApiResponse::success($employmentHistory, "Employment History indexed with sucess", 200);
    }
}
