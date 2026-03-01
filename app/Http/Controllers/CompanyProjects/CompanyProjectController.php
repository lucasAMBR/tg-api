<?php

namespace App\Http\Controllers\CompanyProjects;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyProjects\StoreCompanyProjectRequest;
use App\Models\CompanyProject;
use App\Services\CompanyProjects\CompanyProjectService;
use Illuminate\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyProjectController extends Controller
{
    
    use Authenticatable;

    protected CompanyProjectService $companyProject;

    public function __construct(CompanyProjectService $companyProject){
        $this->companyProject = $companyProject;
    }

    public function store(StoreCompanyProjectRequest $request): JsonResponse {
        
        $company = $this->companyProject->store($request->validated());

        return ApiResponse::success($company, 'Project created with success!', 201);

    }
    
    public function update(StoreCompanyProjectRequest $request, CompanyProject $companyProject){
        //
    }

    public function destroy(CompanyProject $companyProject) {
        //
    }

    public function index() {
        //
    }


}
