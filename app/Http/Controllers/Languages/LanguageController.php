<?php

namespace App\Http\Controllers\Languages;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Languages\IndexLanguageRequest;
use App\Http\Requests\Languages\StoreLanguageRequest;
use App\Http\Requests\Languages\UpdateLanguageRequest;
use App\Models\Language;
use App\Services\Languages\LanguageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class LanguageController extends Controller
{

    use AuthorizesRequests;

    protected LanguageService $languageService;

    public function __construct(LanguageService $languageService)
    {
        return $this->languageService = $languageService;
    }

    public function index(IndexLanguageRequest $request)
    {
        $languages = $this->languageService->index($request->validated());

        return ApiResponse::success($languages, "Languages indexed with success!");
    }

    public function store(StoreLanguageRequest $request) {
        $language = $this->languageService->store($request->validated());

        return ApiResponse::success($language, 'Language created with success!', 201);
    }

    public function update(Language $language, UpdateLanguageRequest $request)
    {
        $this->authorize('update', $language);

        $language = $this->languageService->update($language, $request->validated());

        return ApiResponse::success($language, 'Language updated with success!');
    }

    public function delete(Language $language)
    {
        $this->authorize('delete', $language);

        $this->languageService->delete($language);

        return ApiResponse::success(message: "Language removed with success!");
    }

    public function approveLanguage(Language $language)
    {
        $this->authorize('approve', $language);

        $this->approveLanguage($language);

        return ApiResponse::success(message: "Language approved with success!");
    }
}
