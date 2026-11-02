<?php

namespace App\Http\Controllers\HardSkill;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\HardSkill\DeleteHardSkillRequest;
use App\Http\Requests\HardSkill\IndexHardSkillRequest;
use App\Http\Requests\HardSkill\ShowHardSkillRequest;
use App\Http\Requests\HardSkill\StoreHardSkillRequest;
use App\Http\Requests\HardSkill\UpdateHardSkillRequest;
use App\Services\HardSkill\HardSkillService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class HardSkillController extends Controller
{
    public function __construct(protected HardSkillService $hardSkillService){}

    #[Endpoint(operationId: 'indexHardSkill', title: 'Listar hard skills', description: '**operationId:** `indexHardSkill` — Lista paginada das hard skills, ordenada por `created_at` decrescente, com filtros opcionais por `dev_profile_id` e `search` (busca em `skill_level` e no nome da linguagem vinculada). Em **200**, `data.data[]` segue o schema **Hard Skill Resource** (`App\\Http\\Resources\\HardSkill\\HardSkillResource`) e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexHardSkillRequest $request): JsonResponse
    {
        try {
            $hardSkills = $this->hardSkillService->index($request->validated());

            return ApiResponse::success($hardSkills, "Hard skills listed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showHardSkill', title: 'Consultar hard skill', description: '**operationId:** `showHardSkill` — Retorna uma hard skill específica pelo identificador na rota. Em **200**, `data` segue o schema **Hard Skill Resource** (`App\\Http\\Resources\\HardSkill\\HardSkillResource`).')]
    public function show(ShowHardSkillRequest $request): JsonResponse
    {
        try {
            $skill = $this->hardSkillService->show($request->validated());

            return ApiResponse::success($skill, "Hard skill found with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeHardSkill', title: 'Cadastrar hard skill', description: '**operationId:** `storeHardSkill` — Cadastra uma hard skill (linguagem + nível) para o perfil de desenvolvedor autenticado e dispara a regeração do embedding do perfil. Em **201**, `data` segue o schema **Hard Skill Resource** (`App\\Http\\Resources\\HardSkill\\HardSkillResource`).')]
    public function store(StoreHardSkillRequest $request): JsonResponse
    {
        try {
            $hardSkill = $this->hardSkillService->store($request->validated());

            return ApiResponse::success($hardSkill, "Hard skill registered with success!", 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateHardSkill', title: 'Atualizar hard skill', description: '**operationId:** `updateHardSkill` — Atualiza uma hard skill existente e dispara a regeração do embedding do perfil. Em **200**, `data` segue o schema **Hard Skill Resource** (`App\\Http\\Resources\\HardSkill\\HardSkillResource`).')]
    public function update(UpdateHardSkillRequest $request): JsonResponse
    {
        try {
            $hardSkill = $this->hardSkillService->update($request->validated());

            return ApiResponse::success($hardSkill, "Hard Skill updated with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'deleteHardSkill', title: 'Remover hard skill', description: '**operationId:** `deleteHardSkill` — Remove uma hard skill e dispara a regeração do embedding do perfil. Em **200**, `data` é `null`.')]
    public function delete(DeleteHardSkillRequest $request): JsonResponse
    {
        try {
            $this->hardSkillService->delete($request->validated());

            return ApiResponse::success(message: "Hard Skill deleted with success!");
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
