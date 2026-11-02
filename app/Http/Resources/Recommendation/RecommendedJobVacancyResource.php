<?php

namespace App\Http\Resources\Recommendation;

use App\Http\Resources\JobVacancy\JobVacancyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vaga recomendada para o dev autenticado, com a aderência entre o embedding do perfil e o
 * da vaga e a nota que ordena o ranking.
 */
class RecommendedJobVacancyResource extends JsonResource
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
            /**
             * Nota que ordena o ranking: a `similarity` com até 10% a mais pelo `score` da
             * empresa (score 100 = similarity × 1,1)
             */
            'ranking_score' => round((float) $this->ranking_score, 4),
            'job_vacancy' => new JobVacancyResource($this->resource),
        ];
    }
}
