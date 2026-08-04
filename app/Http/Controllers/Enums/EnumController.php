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
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Enum\EnumResource;
use App\Http\Resources\Question\QuestionCategoryStackResource;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EnumController extends Controller
{
    #[Endpoint(operationId: 'listSeniorityLevelEnumCases', title: 'Listar níveis de senioridade', description: '**operationId:** `listSeniorityLevelEnumCases` — Lista os casos do enum `SeniorityLevelEnum`. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
    public function listSeniorityLevelEnumCases(): JsonResponse
    {
        try {
            return ApiResponse::success(EnumResource::collection(SeniorityLevelEnum::cases()), "seniority levels listed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'listHardSkillLevelEnumCases', title: 'Listar níveis de hard skill', description: '**operationId:** `listHardSkillLevelEnumCases` — Lista os casos do enum `HardSkillLevelsEnum`. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
    public function listHardSkillLevelEnumCases(): JsonResponse
    {
        try {
            return ApiResponse::success(EnumResource::collection(HardSkillLevelsEnum::cases()), "hard skill level listed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'listEmploymentType', title: 'Listar tipos de vínculo', description: '**operationId:** `listEmploymentType` — Lista os casos do enum `EmploymentType`. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
    public function listEmploymentType(): JsonResponse
    {
        try {
            return ApiResponse::success(EnumResource::collection(EmploymentType::cases()), "employment type listed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'listContractType', title: 'Listar tipos de contrato', description: '**operationId:** `listContractType` — Lista os casos do enum `ContractType`. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
    public function listContractType(): JsonResponse
    {
        try {
            return ApiResponse::success(EnumResource::collection(ContractType::cases()), "contract type listed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'listDegreeLevels', title: 'Listar níveis de formação', description: '**operationId:** `listDegreeLevels` — Lista os casos do enum `DegreeLevelEnum`. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
    public function listDegreeLevels(): JsonResponse
    {
        try {
            return ApiResponse::success(EnumResource::collection(DegreeLevelEnum::cases()), "Degree level listed with success");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'listOperationalSegments', title: 'Listar segmentos de atuação', description: '**operationId:** `listOperationalSegments` — Lista os casos do enum `OperationalSegmentEnum`. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
    public function listOperationalSegments(): JsonResponse
    {
        try {
            return ApiResponse::success(EnumResource::collection(OperationalSegmentEnum::cases()), "Operational Segments listed with success");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'listDevSpecialties', title: 'Listar especialidades de desenvolvedor', description: '**operationId:** `listDevSpecialties` — Lista os casos do enum `DevSpecialtyEnum`. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
    public function listDevSpecialties(): JsonResponse
    {
        try {
            return ApiResponse::success(EnumResource::collection(DevSpecialtyEnum::cases()), "Dev Specialties listed with success");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'listQuestionCategoryStacks', title: 'Listar stacks por especialidade', description: '**operationId:** `listQuestionCategoryStacks` — Lista as stacks de categorias de questão disponíveis para a especialidade do perfil de desenvolvedor autenticado. Em **200**, `data` é agrupado por área (`frontend` e/ou `backend`), cada uma com uma lista no schema **Question Category Stack Resource** (`App\\Http\\Resources\\Question\\QuestionCategoryStackResource`), com os campos `value` e `i18n_key`. Quando o usuário autenticado não possui perfil de desenvolvedor, a resposta é **404**.')]
    public function listQuestionCategoryStacks(): JsonResponse
    {
        try {
            $devProfile = Auth::user()?->dev_profile;

            if (!$devProfile) {
                /**
                 * @status 404
                 *
                 * @body array{error: true, message: string, data: mixed}
                 */
                return ApiResponse::error("Authenticated user has no dev profile", status: 404);
            }

            $specialty = $devProfile->specialty instanceof DevSpecialtyEnum
                ? $devProfile->specialty
                : DevSpecialtyEnum::from($devProfile->specialty);

            $stacks = collect(QuestionCategoryEnum::stacksBySpecialty($specialty))
                ->map(fn (array $cases) => QuestionCategoryStackResource::collection($cases))
                ->all();

            return ApiResponse::success(
                $stacks,
                "Question category stacks listed with success"
            );
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }
}
