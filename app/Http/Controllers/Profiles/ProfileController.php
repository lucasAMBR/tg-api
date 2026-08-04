<?php

namespace App\Http\Controllers\Profiles;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyProfile\SyncCompanyStackRequest;
use App\Http\Requests\Profiles\ClientProfiles\DestroyClientProfileRequest;
use App\Http\Requests\Profiles\ClientProfiles\IndexClientProfileRequest;
use App\Http\Requests\Profiles\ClientProfiles\ShowClientProfileRequest;
use App\Http\Requests\Profiles\ClientProfiles\StoreClientProfileRequest;
use App\Http\Requests\Profiles\ClientProfiles\UpdateClientProfileRequest;
use App\Http\Requests\Profiles\CompanyProfiles\DestroyCompanyProfileRequest;
use App\Http\Requests\Profiles\CompanyProfiles\GetCompanyStackRequest;
use App\Http\Requests\Profiles\CompanyProfiles\IndexCompanyProfileRequest;
use App\Http\Requests\Profiles\CompanyProfiles\ShowCompanyProfileRequest;
use App\Http\Requests\Profiles\CompanyProfiles\StoreCompanyProfileRequest;
use App\Http\Requests\Profiles\CompanyProfiles\UpdateCompanyProfileRequest;
use App\Http\Requests\Profiles\DevProfiles\DestroyDevProfileRequest;
use App\Http\Requests\Profiles\DevProfiles\IndexDevProfileRequest;
use App\Http\Requests\Profiles\DevProfiles\ShowDevProfileRequest;
use App\Http\Requests\Profiles\DevProfiles\StoreDevProfileRequest;
use App\Http\Requests\Profiles\DevProfiles\UpdateDevProfileRequest;
use App\Services\Profiles\ProfileService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{

    public function __construct(protected ProfileService $profileService){}

    #[Endpoint(operationId: 'indexDevProfiles', title: 'Listar perfis de desenvolvedor', description: '**operationId:** `indexDevProfiles` — Lista paginada dos perfis de desenvolvedor, com `address` carregado e filtros opcionais por `search` (nome), `seniority_level`, `specialty`, `open_to_relocation` e `open_to_work`. Em **200**, `data.data[]` segue o schema **Dev Profile Resource** (`App\\Http\\Resources\\Profiles\\DevProfile\\DevProfileResource`) e `data.pagination` traz os metadados de paginação.')]
    public function indexDevProfiles(IndexDevProfileRequest $request): JsonResponse
    {
        try {
            $profiles = $this->profileService->indexDevProfiles($request->validated());

            return ApiResponse::success($profiles, "Dev profiles indexed with success", 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'indexCompanyProfiles', title: 'Listar perfis de empresa', description: '**operationId:** `indexCompanyProfiles` — Lista paginada dos perfis de empresa, com `address` carregado e filtros opcionais por `search` (nome) e `operational_segment`. Em **200**, `data.data[]` segue o schema **Company Profile Resource** (`App\\Http\\Resources\\Profiles\\CompanyProfile\\CompanyProfileResource`) e `data.pagination` traz os metadados de paginação.')]
    public function indexCompanyProfiles(IndexCompanyProfileRequest $request): JsonResponse
    {
        try {
            $profiles = $this->profileService->indexCompanyProfiles($request->validated());

            return ApiResponse::success($profiles, "Company profiles indexed with success", 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'indexClientProfiles', title: 'Listar perfis de cliente', description: '**operationId:** `indexClientProfiles` — Lista paginada dos perfis de cliente, com `address` carregado e filtro opcional `search` (nome). Em **200**, `data.data[]` segue o schema **Client Profile Resource** (`App\\Http\\Resources\\Profiles\\ClientProfile\\ClientProfileResource`) e `data.pagination` traz os metadados de paginação.')]
    public function indexClientProfiles(IndexClientProfileRequest $request): JsonResponse
    {
        try {
            $profiles = $this->profileService->indexClientProfiles($request->validated());

            return ApiResponse::success($profiles, "Client profiles indexed with success", 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showDevProfile', title: 'Consultar perfil de desenvolvedor', description: '**operationId:** `showDevProfile` — Retorna um perfil de desenvolvedor específico, com o `user` carregado. Em **200**, `data` segue o schema **Dev Profile Resource** (`App\\Http\\Resources\\Profiles\\DevProfile\\DevProfileResource`).')]
    public function showDevProfile(ShowDevProfileRequest $request): JsonResponse
    {
        try {
            $profile = $this->profileService->showDevProfile($request->validated());

            return ApiResponse::success($profile, "Dev profile found with success", 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showCompanyProfile', title: 'Consultar perfil de empresa', description: '**operationId:** `showCompanyProfile` — Retorna um perfil de empresa específico, com o `user` carregado. Em **200**, `data` segue o schema **Company Profile Resource** (`App\\Http\\Resources\\Profiles\\CompanyProfile\\CompanyProfileResource`).')]
    public function showCompanyProfile(ShowCompanyProfileRequest $request): JsonResponse
    {
        try {
            $profile = $this->profileService->showCompanyProfile($request->validated());

            return ApiResponse::success($profile, "Company profile found with success", 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'showClientProfile', title: 'Consultar perfil de cliente', description: '**operationId:** `showClientProfile` — Retorna um perfil de cliente específico, com o `user` carregado. Em **200**, `data` segue o schema **Client Profile Resource** (`App\\Http\\Resources\\Profiles\\ClientProfile\\ClientProfileResource`).')]
    public function showClientProfile(ShowClientProfileRequest $request): JsonResponse
    {
        try {
            $profile = $this->profileService->showClientProfile($request->validated());

            return ApiResponse::success($profile, "Client profile found with success", 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeDevProfile', title: 'Cadastrar perfil de desenvolvedor', description: '**operationId:** `storeDevProfile` — Cria o perfil de desenvolvedor do usuário autenticado, dispara o evento de criação, a tradução do conteúdo e a geração do embedding do perfil. Em **201**, `data` segue o schema **Dev Profile Resource** (`App\\Http\\Resources\\Profiles\\DevProfile\\DevProfileResource`).')]
    public function storeDevProfile(StoreDevProfileRequest $request): JsonResponse
    {
        try {
            $profile = $this->profileService->storeDevProfile($request->validated());

            return ApiResponse::success($profile, "Profile created with Success", 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeCompanyProfile', title: 'Cadastrar perfil de empresa', description: '**operationId:** `storeCompanyProfile` — Cria o perfil de empresa do usuário autenticado e dispara a tradução do conteúdo. Em **201**, `data` segue o schema **Company Profile Resource** (`App\\Http\\Resources\\Profiles\\CompanyProfile\\CompanyProfileResource`).')]
    public function storeCompanyProfile(StoreCompanyProfileRequest $request): JsonResponse
    {
        try {
            $profile = $this->profileService->storeCompanyProfile($request->validated());

            return ApiResponse::success($profile, "Profile created with success", 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeClientProfile', title: 'Cadastrar perfil de cliente', description: '**operationId:** `storeClientProfile` — Cria o perfil de cliente do usuário autenticado e dispara a tradução do conteúdo. Em **201**, `data` segue o schema **Client Profile Resource** (`App\\Http\\Resources\\Profiles\\ClientProfile\\ClientProfileResource`).')]
    public function storeClientProfile(StoreClientProfileRequest $request): JsonResponse
    {
        try {
            $profile = $this->profileService->storeClientProfile($request->validated());

            return ApiResponse::success($profile, "Profile created with success", 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateDevProfile', title: 'Atualizar perfil de desenvolvedor', description: '**operationId:** `updateDevProfile` — Atualiza o perfil de desenvolvedor. Requer autorização via `DevProfilePolicy::update`. Quando `bio` muda, a tradução é refeita; quando `bio`, `specialty` ou `seniority_level` mudam, o embedding do perfil é regerado. Em **200**, `data` segue o schema **Dev Profile Resource** (`App\\Http\\Resources\\Profiles\\DevProfile\\DevProfileResource`).')]
    public function updateDevProfile(UpdateDevProfileRequest $request): JsonResponse {

        try {
            $profile = $this->profileService->updateDevProfile($request->validated());

            return ApiResponse::success($profile, "Profile updated with success", 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }

    }

    #[Endpoint(operationId: 'updateCompanyProfile', title: 'Atualizar perfil de empresa', description: '**operationId:** `updateCompanyProfile` — Atualiza o perfil de empresa. Requer autorização via `CompanyProfilePolicy::update`. Quando `bio` muda, a tradução é refeita. Em **200**, `data` segue o schema **Company Profile Resource** (`App\\Http\\Resources\\Profiles\\CompanyProfile\\CompanyProfileResource`).')]
    public function updateCompanyProfile(UpdateCompanyProfileRequest $request): JsonResponse {

        try {
            $profile = $this->profileService->updateCompanyProfile($request->validated());

            return ApiResponse::success($profile, "Profile updated with success", 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }

    }

    #[Endpoint(operationId: 'syncCompanyStack', title: 'Sincronizar stack da empresa', description: '**operationId:** `syncCompanyStack` — Sincroniza as linguagens que compõem a stack da empresa com a lista `languages` enviada. Só o dono do perfil pode executar a sincronização. Em **200**, `data` segue o schema **Company Profile Resource** (`App\\Http\\Resources\\Profiles\\CompanyProfile\\CompanyProfileResource`).')]
    public function syncCompanyStack(SyncCompanyStackRequest $request): JsonResponse
    {
        try {
            $profile = $this->profileService->syncCompanyProfileStacks($request->validated());

            return ApiResponse::success($profile, "Stack sync with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'getCompanyStack', title: 'Consultar stack da empresa', description: '**operationId:** `getCompanyStack` — Retorna as linguagens vinculadas ao perfil de empresa informado. Em **200**, `data[]` segue o schema **Language Resource** (`App\\Http\\Resources\\Language\\LanguageResource`).')]
    public function getCompanyStack(GetCompanyStackRequest $request): JsonResponse
    {
        try {
            $stack = $this->profileService->getCompanyStack($request->validated());

            return ApiResponse::success($stack, "Stack found!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateClientProfile', title: 'Atualizar perfil de cliente', description: '**operationId:** `updateClientProfile` — Atualiza o perfil de cliente. Requer autorização via `ClientProfilePolicy::update`. Quando `bio` muda, a tradução é refeita. Em **200**, `data` segue o schema **Client Profile Resource** (`App\\Http\\Resources\\Profiles\\ClientProfile\\ClientProfileResource`).')]
    public function updateClientProfile(UpdateClientProfileRequest $request): JsonResponse {

        try {
            $profile = $this->profileService->updateClientProfile($request->validated());

            return ApiResponse::success($profile, "Profile updated with success", 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }

    }

    #[Endpoint(operationId: 'destroyDevProfile', title: 'Remover perfil de desenvolvedor', description: '**operationId:** `destroyDevProfile` — Remove o perfil de desenvolvedor. Requer autorização via `DevProfilePolicy::delete`. Em **200**, `data` indica se a exclusão foi efetivada.')]
    public function destroyDevProfile(DestroyDevProfileRequest $request): JsonResponse {

        try {
            $profile = $this->profileService->destroyDevProfile($request->validated());

            return ApiResponse::success($profile, "Profile excluded with success!", 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }

    }

    #[Endpoint(operationId: 'destroyCompanyProfile', title: 'Remover perfil de empresa', description: '**operationId:** `destroyCompanyProfile` — Remove o perfil de empresa. Requer autorização via `CompanyProfilePolicy::delete`. Em **200**, `data` indica se a exclusão foi efetivada.')]
    public function destroyCompanyProfile(DestroyCompanyProfileRequest $request): JsonResponse {

        try {
            $profile = $this->profileService->destroyCompanyProfile($request->validated());

            return ApiResponse::success($profile, "Profile excluded with success!", 200);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }

    }

    #[Endpoint(operationId: 'destroyClientProfile', title: 'Remover perfil de cliente', description: '**operationId:** `destroyClientProfile` — Remove o perfil de cliente. Requer autorização via `ClientProfilePolicy::delete`. Em **200**, `data` indica se a exclusão foi efetivada.')]
    public function destroyClientProfile(DestroyClientProfileRequest $request): JsonResponse {

        try {
            $profile = $this->profileService->destroyClientProfile($request->validated());

            return ApiResponse::success($profile, "Profile excluded with success!", 200);
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
