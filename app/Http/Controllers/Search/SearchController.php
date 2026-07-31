<?php

namespace App\Http\Controllers\Search;

use App\Builder\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchRequest;
use App\Services\Search\SearchService;

class SearchController extends Controller
{
    public function __construct(protected SearchService $searchService) {}

    public function search(SearchRequest $request) {

        $data = $this->searchService->search($request->validated());

        return ApiResponse::success(
            $data,
            'Search results retrieved with success!',
            200
        );
    }

    public function topCompanies() {

        $data = $this->searchService->topCompanies();

        return ApiResponse::success(
            $data,
            'Top companies retrieved with success!',
            200
        );
    }

    public function topDevs() {

        $data = $this->searchService->topDevs();

        return ApiResponse::success(
            $data,
            'Top devs retrieved with success!',
            200
        );
    }

    public function topClients() {

        $data = $this->searchService->topClients();

        return ApiResponse::success(
            $data,
            'Top clients retrieved with success!',
            200
        );
    }

    public function topJobVacancies() {

        $data = $this->searchService->topJobVacancies();

        return ApiResponse::success(
            $data,
            'Top job vacancies retrieved with success!',
            200
        );
    }
}
