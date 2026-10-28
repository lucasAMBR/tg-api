<?php

namespace App\Http\Controllers\Enums;

use App\Builder\ApiResponse;
use App\Enums\ContractType;
use App\Enums\DegreeLevelEnum;
use App\Enums\DevSpecialtyEnum;
use App\Enums\EmploymentType;
use App\Enums\FreelanceFeedPostTypeEnum;
use App\Enums\FreelanceJobTypeEnum;
use App\Enums\FreelanceJobVacancyStatusEnum;
use App\Enums\FreelanceProposalPricingTypeEnum;
use App\Enums\FreelanceProposalStatusEnum;
use App\Enums\FreelanceReviewStatusEnum;
use App\Enums\HardSkillLevelsEnum;
use App\Enums\OperationalSegmentEnum;
use App\Enums\QuestionCategoryEnum;
use App\Enums\ScreeningQuestionTypeEnum;
use App\Enums\SeniorityLevelEnum;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Enum\EnumResource;
use App\Http\Resources\Enum\FreelanceJobTypeResource;
use App\Http\Resources\Question\QuestionCategoryStackResource;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EnumController extends Controller
{
    #[Endpoint(operationId: 'enumSeniority', title: 'Listar níveis de senioridade', description: '**operationId:** `enumSeniority` — Lista os casos do enum `SeniorityLevelEnum`. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
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

    #[Endpoint(operationId: 'enumHardSkillLevel', title: 'Listar níveis de hard skill', description: '**operationId:** `enumHardSkillLevel` — Lista os casos do enum `HardSkillLevelsEnum`. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
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

    #[Endpoint(operationId: 'enumEmploymentType', title: 'Listar tipos de vínculo', description: '**operationId:** `enumEmploymentType` — Lista os casos do enum `EmploymentType`. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
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

    #[Endpoint(operationId: 'enumContractType', title: 'Listar tipos de contrato', description: '**operationId:** `enumContractType` — Lista os casos do enum `ContractType`. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
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

    #[Endpoint(operationId: 'enumDegreeLevel', title: 'Listar níveis de formação', description: '**operationId:** `enumDegreeLevel` — Lista os casos do enum `DegreeLevelEnum`. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
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

    #[Endpoint(operationId: 'enumOperationalSegments', title: 'Listar segmentos de atuação', description: '**operationId:** `enumOperationalSegments` — Lista os casos do enum `OperationalSegmentEnum`. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
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

    #[Endpoint(operationId: 'enumDevSpecialty', title: 'Listar especialidades de desenvolvedor', description: '**operationId:** `enumDevSpecialty` — Lista os casos do enum `DevSpecialtyEnum`. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
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

    #[Endpoint(operationId: 'enumFreelanceJobType', title: 'Listar tipos de serviço freelance', description: '**operationId:** `enumFreelanceJobType` — Lista os casos do enum `FreelanceJobTypeEnum`. Em **200**, `data[]` segue o schema **Freelance Job Type Resource** (`App\\Http\\Resources\\Enum\\FreelanceJobTypeResource`), com os campos `value`, `label`, `i18nKey` e `requires_stack` — este último indica se o tipo exige o envio de linguagens no cadastro da vaga.')]
    public function listFreelanceJobType(): JsonResponse
    {
        try {
            return ApiResponse::success(FreelanceJobTypeResource::collection(FreelanceJobTypeEnum::cases()), "freelance job type listed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'enumFreelanceJobVacancyStatus', title: 'Listar status de vaga freelance', description: '**operationId:** `enumFreelanceJobVacancyStatus` — Lista os casos do enum `FreelanceJobVacancyStatusEnum`, usados no ciclo da vaga freelance: aberta para orçamentos, em andamento depois do contrato fechado e concluída. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
    public function listFreelanceJobVacancyStatus(): JsonResponse
    {
        try {
            return ApiResponse::success(EnumResource::collection(FreelanceJobVacancyStatusEnum::cases()), "freelance job vacancy status listed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'enumFreelanceProposalStatus', title: 'Listar status de orçamento freelance', description: '**operationId:** `enumFreelanceProposalStatus` — Lista os casos do enum `FreelanceProposalStatusEnum`, usados na situação do orçamento de um dev para um freela. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
    public function listFreelanceProposalStatus(): JsonResponse
    {
        try {
            return ApiResponse::success(EnumResource::collection(FreelanceProposalStatusEnum::cases()), "freelance proposal status listed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'enumFreelanceProposalPricingType', title: 'Listar formas de cobrança de orçamento freelance', description: '**operationId:** `enumFreelanceProposalPricingType` — Lista os casos do enum `FreelanceProposalPricingTypeEnum`, que indicam como o dev cobra no orçamento: preço fechado ou valor por hora com as horas estimadas. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
    public function listFreelanceProposalPricingType(): JsonResponse
    {
        try {
            return ApiResponse::success(EnumResource::collection(FreelanceProposalPricingTypeEnum::cases()), "freelance proposal pricing type listed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'enumFreelanceFeedPostType', title: 'Listar tipos de postagem do feed freelance', description: '**operationId:** `enumFreelanceFeedPostType` — Lista os casos do enum `FreelanceFeedPostTypeEnum`, usados nos tipos de postagem do feed do freela: atualização, entrega e review. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
    public function listFreelanceFeedPostType(): JsonResponse
    {
        try {
            return ApiResponse::success(EnumResource::collection(FreelanceFeedPostTypeEnum::cases()), "freelance feed post type listed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'enumFreelanceReviewStatus', title: 'Listar resultados de review freelance', description: '**operationId:** `enumFreelanceReviewStatus` — Lista os casos do enum `FreelanceReviewStatusEnum`, usados no veredito do cliente na review de uma entrega do feed. Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
    public function listFreelanceReviewStatus(): JsonResponse
    {
        try {
            return ApiResponse::success(EnumResource::collection(FreelanceReviewStatusEnum::cases()), "freelance review status listed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'enumQuestionStackBySpecialty', title: 'Listar stacks por especialidade', description: '**operationId:** `enumQuestionStackBySpecialty` — Lista as stacks de categorias de questão disponíveis para a especialidade do perfil de desenvolvedor autenticado. Em **200**, `data` é agrupado por área (`frontend` e/ou `backend`), cada uma com uma lista no schema **Question Category Stack Resource** (`App\\Http\\Resources\\Question\\QuestionCategoryStackResource`), com os campos `value` e `i18n_key`. Quando o usuário autenticado não possui perfil de desenvolvedor, a resposta é **404**.')]
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

    #[Endpoint(operationId: 'enumScreeningQuestionType', title: 'Listar tipos de pergunta de triagem', description: '**operationId:** `enumScreeningQuestionType` — Lista os casos do enum `ScreeningQuestionTypeEnum`, os tipos de pergunta aceitos no questionário de triagem da vaga (`essay`, `single_choice` e `multiple_choice`). Em **200**, `data[]` segue o schema **Enum Resource** (`App\\Http\\Resources\\Enum\\EnumResource`), com os campos `value`, `label` e `i18nKey`.')]
    public function listScreeningQuestionTypes(): JsonResponse
    {
        try {
            return ApiResponse::success(EnumResource::collection(ScreeningQuestionTypeEnum::cases()), "screening question types listed with success!");
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
