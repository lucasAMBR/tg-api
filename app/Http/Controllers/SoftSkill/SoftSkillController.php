<?php

namespace App\Http\Controllers\SoftSkill;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SoftSkill\IndexSoftSkillRequest;
use App\Http\Requests\SoftSkill\StoreCompanySoftSkillRequest;
use App\Http\Requests\SoftSkill\StoreDevSoftSkillRequest;
use App\Http\Requests\SoftSkill\UpdateCompanySoftSKillRequest;
use App\Http\Requests\SoftSkill\UpdateDevSoftSkillRequest;
use App\Models\DevProfile;
use App\Services\SoftSkill\SoftSkillService;
class SoftSkillController extends Controller
{
    public function __construct(protected SoftSkillService $softSkillService){}

    public function index(IndexSoftSkillRequest $request)
    {
        $softSkill = $this->softSkillService->index($request->validated());

        return ApiResponse::success($softSkill, "Soft skills listed with success!");
    }

    public function listDevSoftSkill(DevProfile $profile){
        $softSkill = $this->softSkillService->getDevSoftSkillsByProfileId($profile);

        return ApiResponse::success($softSkill, "Dev soft skills found!");
    }

    public function storeDevSoftSkills(StoreDevSoftSkillRequest $request)
    {
        $softSkills = $this->softSkillService->storeDevSoftSkills($request->validated());

        return ApiResponse::success($softSkills, 'Soft skill registered with success!');
    }

    public function updateDevSoftSkills(UpdateDevSoftSkillRequest $request)
    {
        $softSkill = $this->softSkillService->updateDevSoftSkills($request->validated());

        return ApiResponse::success($softSkill, 'Soft skills updated with success!');
    }

    public function storeCompanySoftSkills(StoreCompanySoftSkillRequest $request) {

        $softSkill = $this->softSkillService->storeCompanySoftSkills($request->validated());

        return ApiResponse::success($softSkill, 'Soft skills registered with success!', 200);
    }

    public function updateCompanySoftSkills(UpdateCompanySoftSKillRequest $request, CompanySoftSkill $companySoft) {

        $softSkill = $this->softSkillService->updateCompanySoftSkills($request->validated(), $companySoft);

        return ApiResponse::success($softSkill, 'Soft skills updated with success!', 200);
    }

    public function destroyCompanySoftSkills(CompanySoftSkill $companySoft) {

        $softSkill = $this->softSkillService->destroyCompanySoftSkills($companySoft);

        return ApiResponse::success($softSkill, 'Soft skill deleted with success!', 200);
    }

    public function indexCompanySoftSkills() {

        $softSkill = $this->softSkillService->indexCompanySoftSkills();

        return ApiResponse::success($softSkill, 'Soft skills indexed with success!', 200);
    }
}

