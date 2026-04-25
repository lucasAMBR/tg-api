<?php

namespace App\Http\Controllers\ProjectHistory;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectHistory\IndexProjectHistoryRequest;
use App\Http\Requests\ProjectHistory\SaveImagesToProjectRequest;
use App\Http\Requests\ProjectHistory\StoreProjectHistoryRequest;
use App\Http\Requests\ProjectHistory\UpdateProjectHistoryRequest;
use App\Models\ProjectHistory;
use App\Services\ProjectHistory\ProjectHistoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ProjectHistoryController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected ProjectHistoryService $projectHistoryService){}

    public function index(IndexProjectHistoryRequest $request)
    {
        $projectHistories = $this->projectHistoryService->index($request->validated());

        return ApiResponse::success($projectHistories, "Project History indexed with success!");
    }

    public function show(ProjectHistory $projectHistory){
        $projectHistoryItem = $this->projectHistoryService->show($projectHistory);

        return ApiResponse::success($projectHistoryItem, "Project History Founded!");
    }

    public function store(StoreProjectHistoryRequest $request)
    {
        $projectHistory = $this->projectHistoryService->store($request->validated());

        return ApiResponse::success($projectHistory, "Project registered with success!", 201);
    }

    public function update(ProjectHistory $projectHistory, UpdateProjectHistoryRequest $request)
    {
        $this->authorize('update', $projectHistory);

        $projectHistory = $this->projectHistoryService->update($projectHistory, $request->validated());

        return ApiResponse::success($projectHistory, "Project updated with success!");
    }

    public function delete(ProjectHistory $projectHistory)
    {
        $this->authorize('delete', $projectHistory);

        $this->projectHistoryService->delete($projectHistory);

        return ApiResponse::success(message: "Project removed with success!");
    }

    public function saveImagesInProject(ProjectHistory $projectHistory, SaveImagesToProjectRequest $request)
    {
        $this->authorize('update', $projectHistory);

        $projectHistory = $this->projectHistoryService->saveImagesInProject($projectHistory, $request->validated());

        return ApiResponse::success($projectHistory, "Image saved with success!");
    }

    public function removeImageFromProject(ProjectHistory $projectHistory, int $imageId)
    {
        $this->authorize('update', $projectHistory);

        $this->projectHistoryService->removeImageFromProject($projectHistory, $imageId);

        return ApiResponse::success(message: "Image Removed with success!");
    }
}
