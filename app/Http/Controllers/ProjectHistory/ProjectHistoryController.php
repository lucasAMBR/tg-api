<?php

namespace App\Http\Controllers\ProjectHistory;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectHistory\SaveImagesToProjectRequest;
use App\Http\Requests\ProjectHistory\StoreProjectHistoryRequest;
use App\Models\ProjectHistory;
use App\Services\ProjectHistory\ProjectHistoryService;
use Illuminate\Http\Request;

class ProjectHistoryController extends Controller
{
    public function __construct(protected ProjectHistoryService $projectHistoryService){}

    public function store(StoreProjectHistoryRequest $request)
    {
        $projectHistory = $this->projectHistoryService->store($request->validated());

        return ApiResponse::success($projectHistory, "Project registered with success!");
    }

    public function saveImagesInProject(ProjectHistory $projectHistory, SaveImagesToProjectRequest $request)
    {
        $projectHistory = $this->projectHistoryService->saveImagesInProject($projectHistory, $request->validated());

        return ApiResponse::success($projectHistory, "Images saved with success!");
    }
}
