<?php

namespace App\Http\Resources\Recommendation;

use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Perfil de desenvolvedor recomendado para uma vaga, acompanhado do seu grau de
 * aderência ao embedding da vaga, da nota que ordena o ranking e do que ele já fez
 * nela: se já se candidatou e se a empresa já o convidou.
 */
class RecommendedDevResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{similarity: float, ranking_score: float, has_applied: bool, is_invited: bool, dev_profile: DevProfileResource}
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
            /**
             * Se o dev já se candidatou à vaga
             */
            'has_applied' => (bool) $this->has_applied,
            /**
             * Se a empresa já convidou o dev para o processo seletivo
             * (`POST /job-vacancy/{id}/invitations`)
             */
            'is_invited' => (bool) $this->is_invited,
            'dev_profile' => new DevProfileResource($this->resource),
        ];
    }
}
