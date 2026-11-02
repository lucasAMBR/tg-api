<?php

namespace App\Http\Resources\Recommendation;

use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Perfil de desenvolvedor recomendado para uma vaga, acompanhado do seu grau de
 * aderência ao embedding da vaga e da nota que ordena o ranking.
 */
class RecommendedDevResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{similarity: float, ranking_score: float, dev_profile: DevProfileResource}
     */
    public function toArray(Request $request): array
    {
        return [
            'similarity' => round((float) $this->similarity, 4),
            /**
             * Nota que ordena o ranking: a `similarity` com até 10% a mais pelo `score` do dev
             * (score 100 = similarity × 1,1)
             */
            'ranking_score' => round((float) $this->ranking_score, 4),
            'dev_profile' => new DevProfileResource($this->resource),
        ];
    }
}
