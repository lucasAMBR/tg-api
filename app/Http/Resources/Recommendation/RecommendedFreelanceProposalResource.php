<?php

namespace App\Http\Resources\Recommendation;

use App\Http\Resources\FreelanceProposal\FreelanceProposalResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Orçamento ranqueado para o cliente: o `score` combina a aderência do dev ao freela
 * (`similarity`) com a proximidade do preço ao budget (`price_score`), todos de 0 a 1.
 */
class RecommendedFreelanceProposalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'score' => round((float) $this->score, 4),
            /**
             * `null` quando o perfil do dev ainda não tem embedding: aí o score vem só do preço
             */
            'similarity' => is_null($this->similarity) ? null : round((float) $this->similarity, 4),
            'price_score' => round((float) $this->price_score, 4),
            'proposal' => new FreelanceProposalResource($this->resource),
        ];
    }
}
