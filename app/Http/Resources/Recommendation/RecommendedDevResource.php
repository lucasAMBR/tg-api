<?php

namespace App\Http\Resources\Recommendation;

use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Perfil de desenvolvedor recomendado para uma vaga, acompanhado do seu grau de
 * aderência ao embedding da vaga.
 */
class RecommendedDevResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{similarity: float, dev_profile: DevProfileResource}
     */
    public function toArray(Request $request): array
    {
        return [
            'similarity' => round((float) $this->similarity, 4),
            'dev_profile' => new DevProfileResource($this->resource),
        ];
    }
}
