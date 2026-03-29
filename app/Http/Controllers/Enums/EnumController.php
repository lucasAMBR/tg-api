<?php

namespace App\Http\Controllers\Enums;

use App\Builder\ApiResponse;
use App\Enums\HardSkillLevelsEnum;
use App\Enums\SeniorityLevelEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EnumController extends Controller
{
    public function listSeniorityLevelEnumCases()
    {
        return ApiResponse::success(SeniorityLevelEnum::options(), "seniority levels listed with success!");
    }

    public function listHardSkillLevelEnumCases()
    {
        return ApiResponse::success(HardSkillLevelsEnum::options(), "hard skill level listed with success!");
    }
}
