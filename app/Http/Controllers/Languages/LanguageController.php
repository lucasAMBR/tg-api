<?php

namespace App\Http\Controllers\Languages;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Languages\ApproveLanguageRequest;
use App\Http\Requests\Languages\DeleteLanguageRequest;
use App\Http\Requests\Languages\IndexLanguageRequest;
use App\Http\Requests\Languages\StoreLanguageRequest;
use App\Http\Requests\Languages\UpdateLanguageRequest;
use App\Services\Languages\LanguageService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class LanguageController extends Controller
{

    protected LanguageService $languageService;

    public function __construct(LanguageService $languageService)
    {
        return $this->languageService = $languageService;
    }

    #[Endpoint(operationId: 'indexLanguage', title: 'Listar linguagens', description: '**operationId:** `indexLanguage` — Lista paginada das linguagens aprovadas (`is_approved = true`), com filtro opcional `search` (busca em `name`). Em **200**, `data.data[]` segue o schema **Language Resource** (`App\\Http\\Resources\\Language\\LanguageResource`) e `data.pagination` traz os metadados de paginação.')]
    public function index(IndexLanguageRequest $request): JsonResponse
    {
        try {
            $languages = $this->languageService->index($request->validated());

            return ApiResponse::success($languages, "Languages indexed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'storeLanguage', title: 'Cadastrar linguagem', description: '**operationId:** `storeLanguage` — Cadastra uma linguagem. Os campos `is_oficial` e `is_approved` são opcionais e assumem `false` quando não enviados — linguagens não aprovadas não aparecem na listagem. Em **201**, `data` segue o schema **Language Resource** (`App\\Http\\Resources\\Language\\LanguageResource`).')]
    public function store(StoreLanguageRequest $request): JsonResponse {
        try {
            $language = $this->languageService->store($request->validated());

            return ApiResponse::success($language, 'Language created with success!', 201);
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'updateLanguage', title: 'Atualizar linguagem', description: '**operationId:** `updateLanguage` — Atualiza uma linguagem existente. Requer autorização via `LanguagePolicy::update`. Em **200**, `data` segue o schema **Language Resource** (`App\\Http\\Resources\\Language\\LanguageResource`).')]
    public function update(UpdateLanguageRequest $request): JsonResponse
    {
        try {
            $language = $this->languageService->update($request->validated());

            return ApiResponse::success($language, 'Language updated with success!');
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'deleteLanguage', title: 'Remover linguagem', description: '**operationId:** `deleteLanguage` — Remove uma linguagem. Requer autorização via `LanguagePolicy::delete`. Em **200**, `data` é `null`.')]
    public function delete(DeleteLanguageRequest $request): JsonResponse
    {
        try {
            $this->languageService->delete($request->validated());

            return ApiResponse::success(message: "Language removed with success!");
        } catch (ApiException $e) {
            /**
             * @status 400
             *
             * @body array{error: true, message: string, data: mixed}
             */
            return ApiResponse::error($e->getMessage(), $e->data, $e->getCode());
        }
    }

    #[Endpoint(operationId: 'approveLanguage', title: 'Aprovar linguagem', description: '**operationId:** `approveLanguage` — Marca a linguagem como aprovada (`is_approved = true`), tornando-a visível na listagem. Requer autorização via `LanguagePolicy::approve`. Em **200**, `data` é `null`.')]
    public function approveLanguage(ApproveLanguageRequest $request): JsonResponse
    {
        try {
            $this->languageService->approveLanguage($request->validated());

            return ApiResponse::success(message: "Language approved with success!");
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
