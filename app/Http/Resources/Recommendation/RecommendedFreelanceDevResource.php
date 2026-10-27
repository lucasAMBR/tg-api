<?php

namespace App\Http\Resources\Recommendation;

use App\Http\Resources\Profiles\DevProfile\DevProfileSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dev recomendado para um freela, com a aderência ao embedding do freela e o que ele
 * já fez nele: se orçou e se o cliente já o convidou.
 */
class RecommendedFreelanceDevResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'similarity' => round((float) $this->similarity, 4),
            'has_proposal' => (bool) $this->has_proposal,
            'is_invited' => (bool) $this->is_invited,
            'dev_profile' => new DevProfileSummaryResource($this->resource),
        ];
    }
}
