<?php

namespace App\Http\Resources\FreelanceJobVacancy;

use App\Http\Resources\FreelanceProposal\FreelanceProposalResource;
use App\Http\Resources\Profiles\ClientProfile\ClientProfileSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vaga freelance. Parte dos campos depende de quem está vendo: o cliente dono vê o
 * `budget` e o `proposals_count`; o dev vê o `my_proposal` e o `is_invited`; o `contract`
 * aparece só para as duas pontas do contrato.
 */
class FreelanceJobVacancyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();

        $isOwner = $viewer?->client_profile?->id === $this->client_profile_id;

        $contractLoaded = $this->relationLoaded('acceptedProposal');
        $isContractedDev = $contractLoaded
            && $this->acceptedProposal
            && $viewer?->dev_profile?->id === $this->acceptedProposal->dev_profile_id;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'job_type' => $this->job_type?->value,
            'job_type_label' => $this->job_type?->label(),
            'requires_stack' => $this->job_type?->requiresStack(),
            'seniority_level' => $this->seniority_level?->value,
            'seniority_level_label' => $this->seniority_level?->label(),
            'specialties' => $this->specialties,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            /**
             * Quanto o cliente pretende pagar pelo serviço inteiro. Só o dono vê: é a
             * referência de preço do ranking de orçamentos
             */
            'budget' => $this->when($isOwner, $this->budget),
            'languages' => FreelanceJobVacancyLanguageResource::collection($this->whenLoaded('languages')),
            'client_profile_id' => $this->client_profile_id,
            'profile' => $this->whenLoaded('clientProfile', fn() => $this->clientProfile ? new ClientProfileSummaryResource($this->clientProfile) : null),
            /**
             * Quantos orçamentos a vaga recebeu. Só o dono vê
             */
            'proposals_count' => $this->when($isOwner, fn() => $this->whenCounted('proposals')),
            /**
             * O orçamento escolhido pelo cliente, que fechou o contrato. Só o dono e o
             * dev contratado veem; `null` enquanto o freela está aberto
             */
            'contract' => $this->when(
                $contractLoaded && ($isOwner || $isContractedDev),
                fn() => $this->acceptedProposal ? new FreelanceProposalResource($this->acceptedProposal) : null
            ),
            /**
             * O orçamento que o dev autenticado já enviou para a vaga (`null` se ainda não orçou)
             */
            'my_proposal' => $this->whenLoaded('proposals', fn() => $this->proposals->first() ? new FreelanceProposalResource($this->proposals->first()) : null),
            /**
             * Se o cliente convidou o dev autenticado para orçar a vaga
             */
            'is_invited' => $this->whenHas('is_invited', fn($isInvited) => (bool) $isInvited),
            'contracted_at' => $this->contracted_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
