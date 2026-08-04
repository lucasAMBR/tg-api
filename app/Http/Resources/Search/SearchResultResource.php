<?php

namespace App\Http\Resources\Search;

use App\Http\Resources\JobVacancy\JobVacancyResource;
use App\Http\Resources\Profiles\ClientProfile\ClientProfileResource;
use App\Http\Resources\Profiles\CompanyProfile\CompanyProfileResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
     * Mapa de grupo => resource responsável por serializar seus itens.
     *
     * @var array<string, class-string<JsonResource>>
     */
    private const GROUPS = [
        'dev_profiles' => DevProfileResource::class,
        'company_profiles' => CompanyProfileResource::class,
        'client_profiles' => ClientProfileResource::class,
        'job_vacancies' => JobVacancyResource::class,
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $result = [];
        $paginators = [];

        foreach (self::GROUPS as $group => $resource) {
            /** @var LengthAwarePaginator $paginator */
            $paginator = $this->resource[$group];
            $paginators[] = $paginator;

            $result[$group] = [
                'data' => $resource::collection($paginator->getCollection()),
                'pagination' => $this->paginationMeta($paginator),
            ];
        }

        $result['pagination'] = $this->synchronizedPagination($paginators);

        return $result;
    }

    /**
     * Meta de paginação de um grupo isolado.
     *
     * @return array<string, int>
     */
    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'total' => $paginator->total(),
            'count' => $paginator->count(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'total_pages' => $paginator->lastPage(),
        ];
    }

    /**
     * Meta agregada: permite ao client usar um único controle de paginação.
     *
     * `total_pages` é o maior número de páginas entre os grupos, então a
     * navegação só termina quando todos os grupos se esgotam.
     *
     * @param  array<int, LengthAwarePaginator>  $paginators
     * @return array<string, int|bool>
     */
    private function synchronizedPagination(array $paginators): array
    {
        $first = $paginators[0];

        $currentPage = $first->currentPage();
        $totalPages = max(array_map(fn (LengthAwarePaginator $p) => $p->lastPage(), $paginators));

        return [
            'total' => array_sum(array_map(fn (LengthAwarePaginator $p) => $p->total(), $paginators)),
            'count' => array_sum(array_map(fn (LengthAwarePaginator $p) => $p->count(), $paginators)),
            'per_page' => $first->perPage(),
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'has_more_pages' => $currentPage < $totalPages,
        ];
    }
}
