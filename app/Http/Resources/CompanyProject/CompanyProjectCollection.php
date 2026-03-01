<?php

namespace App\Http\Resources\CompanyProject;

use App\Models\CompanyProject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CompanyProjectCollection extends ResourceCollection
{

    public $collects = CompanyProject::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'paginate' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
            ]
        ];
    }
}
