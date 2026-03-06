<?php

namespace App\Http\Controllers\HardSkill;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\HardSkill\IndexHardSkillRequest;
use App\Http\Requests\HardSkill\StoreHardSkillRequest;
use App\Http\Requests\HardSkill\UpdateHardSkillRequest;
use App\Models\HardSkill;
use App\Services\HardSkill\HardSkillService;
use Illuminate\Http\Request;

class HardSkillController extends Controller
{
    public function __construct(protected HardSkillService $hardSkillService){}

    public function index(IndexHardSkillRequest $request)
    {
        $hardSkills = $this->hardSkillService->index($request->validated());

        return ApiResponse::success($hardSkills, "Hard skills listed with success!");
    }

    public function store(StoreHardSkillRequest $request)
    {
        $hardSkill = $this->hardSkillService->store($request->validated());

        return ApiResponse::success($hardSkill, "Hard skill registered with success!", 201);
    }

    public function update(HardSkill $hardSkill, UpdateHardSkillRequest $request)
    {
        $hardSkill = $this->hardSkillService->update($hardSkill, $request->validated());

        return ApiResponse::success($hardSkill, "Hard Skill updated with success!");
    }

    public function delete(HardSkill $hardSkill)
    {
        $this->hardSkillService->delete($hardSkill);

        return ApiResponse::success(message: "Hard Skill deleted with success!");
    }
}
