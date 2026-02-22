<?php

namespace App\Http\Controllers\Languages;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Languages\StoreLanguageRequest;
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

    public function store(StoreLanguageRequest $request) {

        $language = $this->languageService->store($request->validated());

        return ApiResponse::success($language, 'Language created with success!', 201);

    }

}
