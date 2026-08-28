<?php

namespace App\Http\Controllers\Search;

use App\Builder\ApiResponse;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Search\SearchRequest;
use App\Services\Search\SearchService;
use Dedoc\Scramble\Attributes\Endpoint;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(protected SearchService $searchService) {}

    #[Endpoint(operationId: 'search', title: 'Busca global', description: '**operationId:** `search` — Busca o termo informado em perfis de desenvolvedor (nome, bio, especialidade e senioridade), de empresa (nome, bio e segmento), de cliente (nome e bio) e em vagas (título, descrição, tipo de contrato, senioridade, linguagens e soft skills). O parâmetro `search` é opcional: quando omitido, todos os grupos são listados sem filtro, dos mais recentes para os mais antigos. Os parâmetros `page` (padrão 1) e `per_page` (padrão 10, máximo 50) são aplicados de forma sincronizada a todos os grupos. Em **200**, `data` segue o schema **Search Result Resource** (`App\\Http\\Resources\\Search\\SearchResultResource`): as chaves `dev_profiles`, `company_profiles`, `client_profiles` e `job_vacancies` trazem `data` e `pagination` próprios, e a chave `pagination` na raiz agrega a paginação (`total`, `count`, `per_page`, `current_page`, `total_pages`, `has_more_pages`), onde `total_pages` é o maior número de páginas entre os grupos.')]
    public function search(SearchRequest $request): JsonResponse {

        try {
            $data = $this->searchService->search($request->validated());

            return ApiResponse::success(
                $data,
                'Search results retrieved with success!',
                200
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

    #[Endpoint(operationId: 'topCompanies', title: 'Listar empresas em destaque', description: '**operationId:** `topCompanies` — Retorna os 10 perfis de empresa com maior `score`, do maior para o menor. Em **200**, `data[]` segue o schema **Company Profile Resource** (`App\\Http\\Resources\\Profiles\\CompanyProfile\\CompanyProfileResource`).')]
    public function topCompanies(): JsonResponse {

        try {
            $data = $this->searchService->topCompanies();

            return ApiResponse::success(
                $data,
                'Top companies retrieved with success!',
                200
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

    #[Endpoint(operationId: 'topDevs', title: 'Listar desenvolvedores em destaque', description: '**operationId:** `topDevs` — Retorna os 10 perfis de desenvolvedor com maior `score`, do maior para o menor. Em **200**, `data[]` segue o schema **Dev Profile Resource** (`App\\Http\\Resources\\Profiles\\DevProfile\\DevProfileResource`).')]
    public function topDevs(): JsonResponse {

        try {
            $data = $this->searchService->topDevs();

            return ApiResponse::success(
                $data,
                'Top devs retrieved with success!',
                200
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

    #[Endpoint(operationId: 'topClients', title: 'Listar clientes em destaque', description: '**operationId:** `topClients` — Retorna os 10 perfis de cliente com maior `score`, do maior para o menor. Em **200**, `data[]` segue o schema **Client Profile Resource** (`App\\Http\\Resources\\Profiles\\ClientProfile\\ClientProfileResource`).')]
    public function topClients(): JsonResponse {

        try {
            $data = $this->searchService->topClients();

            return ApiResponse::success(
                $data,
                'Top clients retrieved with success!',
                200
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

    #[Endpoint(operationId: 'topJobVacancies', title: 'Listar vagas em destaque', description: '**operationId:** `topJobVacancies` — Retorna as 10 vagas com mais candidaturas, com `softSkill` e `languages` carregados e a contagem `dev_profiles_count`. Em **200**, `data[]` segue o schema **Job Vacancy Resource** (`App\\Http\\Resources\\JobVacancy\\JobVacancyResource`).')]
    public function topJobVacancies(): JsonResponse {

        try {
            $data = $this->searchService->topJobVacancies();

            return ApiResponse::success(
                $data,
                'Top job vacancies retrieved with success!',
                200
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
