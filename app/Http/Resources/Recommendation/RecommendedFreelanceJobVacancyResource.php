<?php

namespace App\Http\Resources\Recommendation;

use App\Http\Resources\FreelanceJobVacancy\FreelanceJobVacancyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Freela recomendado para o dev autenticado, com a aderência entre o embedding do perfil
 * e o do freela.
 */
class RecommendedFreelanceJobVacancyResource extends JsonResource
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
            'freelance_job_vacancy' => new FreelanceJobVacancyResource($this->resource),
        ];
    }
}
