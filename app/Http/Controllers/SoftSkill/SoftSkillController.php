<?php

namespace App\Http\Controllers\SoftSkill;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SoftSkill\IndexCompanySoftSkillRequest;
use App\Http\Requests\SoftSkill\IndexSoftSkillRequest;
use App\Http\Requests\SoftSkill\ListDevSoftSkillRequest;
use App\Http\Requests\SoftSkill\StoreCompanySoftSkillRequest;
use App\Http\Requests\SoftSkill\StoreDevSoftSkillRequest;
use App\Http\Requests\SoftSkill\UpdateDevSoftSkillRequest;
use App\Services\SoftSkill\SoftSkillService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class SoftSkillController extends Controller
{
    public function __construct(protected SoftSkillService $softSkillService){}

    #[Endpoint(operationId: 'indexSoftSkill', title: 'Listar soft skills', description: '**operationId:** `indexSoftSkill` — Lista todas as soft skills disponíveis com seus níveis de resposta (`responses`) carregados. Em **200**, `data[]` segue o schema **Soft Skill Resource** (`App\\Http\\Resources\\SoftSkill\\SoftSkillResource`).')]
    public function index(IndexSoftSkillRequest $request): JsonResponse
    {
        try {
            $softSkill = $this->softSkillService->index($request->validated());

            return ApiResponse::success($softSkill, "Soft skills listed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'listDevSoftSkill', title: 'Listar soft skills do desenvolvedor', description: '**operationId:** `listDevSoftSkill` — Retorna as soft skills do perfil de desenvolvedor informado, ordenadas pelo peso de avaliação do nível escolhido (do maior para o menor). Em **200**, `data[]` segue o schema **Dev Soft Skill Resource** (`App\\Http\\Resources\\DevSoftSkill\\DevSoftSkillResource`).')]
    public function listDevSoftSkill(ListDevSoftSkillRequest $request): JsonResponse {
        try {
            $softSkill = $this->softSkillService->getDevSoftSkillsByProfileId($request->validated());

            return ApiResponse::success($softSkill, "Dev soft skills found!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeDevSoftSkill', title: 'Cadastrar soft skills do desenvolvedor', description: '**operationId:** `storeDevSoftSkill` — Registra as soft skills do perfil de desenvolvedor autenticado. A soma dos pesos dos níveis escolhidos precisa ficar dentro do limite da senioridade do perfil e ser de no mínimo 10 pontos; cada nível informado precisa pertencer à soft skill correspondente. Dispara a regeração do embedding do perfil. Em **200**, `data[]` segue o schema **Dev Soft Skill Resource** (`App\\Http\\Resources\\DevSoftSkill\\DevSoftSkillResource`).')]
    public function storeDevSoftSkills(StoreDevSoftSkillRequest $request): JsonResponse
    {
        try {
            $softSkills = $this->softSkillService->storeDevSoftSkills($request->validated());

            return ApiResponse::success($softSkills, 'Soft skill registered with success!');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateDevSoftSkill', title: 'Atualizar soft skills do desenvolvedor', description: '**operationId:** `updateDevSoftSkill` — Atualiza o nível das soft skills já registradas no perfil de desenvolvedor autenticado, respeitando o limite de pontos da senioridade e o mínimo de 10 pontos. Dispara a regeração do embedding do perfil. Em **200**, `data[]` segue o schema **Dev Soft Skill Resource** (`App\\Http\\Resources\\DevSoftSkill\\DevSoftSkillResource`).')]
    public function updateDevSoftSkills(UpdateDevSoftSkillRequest $request): JsonResponse
    {
        try {
            $softSkill = $this->softSkillService->updateDevSoftSkills($request->validated());

            return ApiResponse::success($softSkill, 'Soft skills updated with success!');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeCompanySoftSkill', title: 'Sincronizar soft skills da empresa', description: '**operationId:** `storeCompanySoftSkill` — Substitui as soft skills do perfil de empresa pela lista `soft_skills` enviada (duplicatas são ignoradas). Só o dono do perfil pode executar a sincronização. Em **200**, `data[]` segue o schema **Company Soft Skill Resource** (`App\\Http\\Resources\\CompanySoftSkill\\CompanySoftSkillResource`).')]
    public function syncCompanySoftSkills(StoreCompanySoftSkillRequest $request): JsonResponse
    {
        try {
            $softSkill = $this->softSkillService->syncCompanySoftSkills($request->validated());

            return ApiResponse::success($softSkill, 'Soft skills synced with success!', 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'indexCompanySoftSkills', title: 'Listar soft skills da empresa', description: '**operationId:** `indexCompanySoftSkills` — Retorna as soft skills vinculadas ao perfil de empresa informado. Em **200**, `data[]` segue o schema **Company Soft Skill Resource** (`App\\Http\\Resources\\CompanySoftSkill\\CompanySoftSkillResource`).')]
    public function indexCompanySoftSkills(IndexCompanySoftSkillRequest $request): JsonResponse
    {
        try {
            $softSkill = $this->softSkillService->indexCompanySoftSkills($request->validated());

            return ApiResponse::success($softSkill, 'Soft skills indexed with success!', 200);
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
