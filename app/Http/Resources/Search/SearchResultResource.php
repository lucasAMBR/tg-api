<?php

namespace App\Http\Resources\Search;

use App\Http\Resources\JobVacancy\JobVacancyResource;
use App\Http\Resources\Profiles\ClientProfile\ClientProfileResource;
use App\Http\Resources\Profiles\CompanyProfile\CompanyProfileResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resultado da busca global, agrupado por tipo de registro.
 *
 * Todos os grupos são paginados com a mesma `page` e o mesmo `per_page`,
 * de forma que uma única navegação no client avança todos ao mesmo tempo.
 */
class SearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Os grupos são montados um a um, com as chaves escritas literalmente,
     * porque o Scramble analisa este método estaticamente: um `foreach` sobre
     * um mapa de grupos deixaria o schema sem nenhuma propriedade conhecida.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var LengthAwarePaginator $devProfiles */
        $devProfiles = $this->resource['dev_profiles'];
        /** @var LengthAwarePaginator $companyProfiles */
        $companyProfiles = $this->resource['company_profiles'];
        /** @var LengthAwarePaginator $clientProfiles */
        $clientProfiles = $this->resource['client_profiles'];
        /** @var LengthAwarePaginator $jobVacancies */
        $jobVacancies = $this->resource['job_vacancies'];

        return [
            'dev_profiles' => [
                'data' => DevProfileResource::collection($devProfiles->getCollection()),
                'pagination' => $this->paginationMeta($devProfiles),
            ],
            'company_profiles' => [
                'data' => CompanyProfileResource::collection($companyProfiles->getCollection()),
                'pagination' => $this->paginationMeta($companyProfiles),
            ],
            'client_profiles' => [
                'data' => ClientProfileResource::collection($clientProfiles->getCollection()),
                'pagination' => $this->paginationMeta($clientProfiles),
            ],
            'job_vacancies' => [
                'data' => JobVacancyResource::collection($jobVacancies->getCollection()),
                'pagination' => $this->paginationMeta($jobVacancies),
            ],
            'pagination' => $this->synchronizedPagination([
                $devProfiles,
                $companyProfiles,
                $clientProfiles,
                $jobVacancies,
            ]),
        ];
    }

    /**
     * Meta de paginação de um grupo isolado.
     *
     * @return array{total: int, count: int, per_page: int, current_page: int, total_pages: int}
     */
    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'total' => (int) $paginator->total(),
            'count' => (int) $paginator->count(),
            'per_page' => (int) $paginator->perPage(),
            'current_page' => (int) $paginator->currentPage(),
            'total_pages' => (int) $paginator->lastPage(),
        ];
    }

    /**
     * Meta agregada: permite ao client usar um único controle de paginação.
     *
     * `total_pages` é o maior número de páginas entre os grupos, então a
     * navegação só termina quando todos os grupos se esgotam.
     *
     * @param  array<int, LengthAwarePaginator>  $paginators
     * @return array{total: int, count: int, per_page: int, current_page: int, total_pages: int, has_more_pages: bool}
     */
    private function synchronizedPagination(array $paginators): array
    {
        $first = $paginators[0];

        $currentPage = (int) $first->currentPage();
        $totalPages = (int) max(array_map(fn (LengthAwarePaginator $p) => $p->lastPage(), $paginators));

        return [
            'total' => (int) array_sum(array_map(fn (LengthAwarePaginator $p) => $p->total(), $paginators)),
            'count' => (int) array_sum(array_map(fn (LengthAwarePaginator $p) => $p->count(), $paginators)),
            'per_page' => (int) $first->perPage(),
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'has_more_pages' => (bool) ($currentPage < $totalPages),
        ];
    }
}
