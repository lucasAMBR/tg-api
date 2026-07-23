<?php

namespace App\Http\Controllers\Enums;

use App\Builder\ApiResponse;
use App\Enums\ContractType;
use App\Enums\DegreeLevelEnum;
use App\Enums\DevSpecialtyEnum;
use App\Enums\EmploymentType;
use App\Enums\HardSkillLevelsEnum;
use App\Enums\OperationalSegmentEnum;
use App\Enums\QuestionCategoryEnum;
use App\Enums\SeniorityLevelEnum;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

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

    public function listDevSpecialties()
    {
        return ApiResponse::success(DevSpecialtyEnum::options(), "Dev Specialties listed with success");
    }

    public function listQuestionCategoryStacks()
    {
        $devProfile = Auth::user()?->dev_profile;

        if (!$devProfile) {
            return ApiResponse::error("Authenticated user has no dev profile", status: 404);
        }

        $specialty = $devProfile->specialty instanceof DevSpecialtyEnum
            ? $devProfile->specialty
            : DevSpecialtyEnum::from($devProfile->specialty);

        return ApiResponse::success(
            QuestionCategoryEnum::stacksBySpecialty($specialty),
            "Question category stacks listed with success"
        );
    }
}
