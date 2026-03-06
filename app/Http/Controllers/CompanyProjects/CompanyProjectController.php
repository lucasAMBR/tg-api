<?php

namespace App\Http\Controllers\CompanyProjects;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyProjects\IndexCompanyProjectsRequest;
use App\Http\Requests\CompanyProjects\StoreCompanyProjectRequest;
use App\Http\Requests\CompanyProjects\UpdateCompanyProjectsRequest;
use App\Models\CompanyProject;
use App\Services\CompanyProjects\CompanyProjectService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class CompanyProjectController extends Controller
{
    
    use AuthorizesRequests;

    protected CompanyProjectService $companyProject;

    public function __construct(CompanyProjectService $companyProject){
        $this->companyProject = $companyProject;
    }

    public function store(StoreCompanyProjectRequest $request): JsonResponse {
        
        $project = $this->companyProject->store($request->validated());

        return ApiResponse::success($project, 'Project created with success!', 201);

    }
    
    public function update(UpdateCompanyProjectsRequest $request, CompanyProject $companyProject): JsonResponse {

        $this->authorize('update', $companyProject);

        $companyProject = $this->companyProject->update($request->validated(), $companyProject);

        return ApiResponse::success($companyProject, 'Project updated with success!', 200);

    }

    public function destroy(CompanyProject $companyProject) {

        $this->authorize('delete', $companyProject);
        
        $companyProject = $this->companyProject->destroy($companyProject);

        return ApiResponse::success($companyProject, 'Project deleted with success!', 200);

    }

    public function index(IndexCompanyProjectsRequest $request) {
        
        $companyProject = $this->companyProject->index($request->validated());

        return ApiResponse::success($companyProject, 'Projects indexed with success!', 200);

    }


}
