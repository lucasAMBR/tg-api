<?php

namespace App\Http\Controllers\Enums;

use App\Builder\ApiResponse;
use App\Enums\ContractType;
use App\Enums\DegreeLevelEnum;
use App\Enums\EmploymentType;
use App\Enums\HardSkillLevelsEnum;
use App\Enums\OperationalSegmentEnum;
use App\Enums\SeniorityLevelEnum;
use App\Http\Controllers\Controller;

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

    public function listEmploymentType()
    {
        return ApiResponse::success(EmploymentType::options(), "employment type listed with success!");
    }

    public function listContractType()
    {
        return ApiResponse::success(ContractType::options(), "contract type listed with success!");
    }

    public function listDegreeLevels()
    {
        return ApiResponse::success(DegreeLevelEnum::options(), "Degree level listed with success");
    }

    public function listOperationalSegments()
    {
        return ApiResponse::success(OperationalSegmentEnum::options(), "Operational Segments listed with success");
    }
}
