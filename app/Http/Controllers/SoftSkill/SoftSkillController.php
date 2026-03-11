<?php

namespace App\Http\Controllers\SoftSkill;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\SoftSkill\IndexSoftSkillRequest;
use App\Http\Requests\SoftSkill\StoreDevSoftSkillRequest;
use App\Services\SoftSkill\SoftSkillService;
use Illuminate\Http\Request;

class SoftSkillController extends Controller
{
    public function __construct(protected SoftSkillService $softSkillService){}

    public function index(IndexSoftSkillRequest $request)
    {
        $softSkill = $this->softSkillService->index($request->validated());

        return ApiResponse::success($softSkill, "Soft skills listed with success!");
    }

    public function storeDevSoftSkills(StoreDevSoftSkillRequest $request)
    {
        $softSkills = $this->softSkillService->storeDevSoftSkills($request->validated());

        return ApiResponse::success($softSkills, 'Soft skill registered with success!');
    }
}
