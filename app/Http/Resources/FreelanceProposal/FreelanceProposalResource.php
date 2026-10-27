<?php

namespace App\Http\Resources\FreelanceProposal;

use App\Http\Resources\FreelanceJobVacancy\FreelanceJobVacancyResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FreelanceProposalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'freelance_job_vacancy_id' => $this->freelance_job_vacancy_id,
            'dev_profile_id' => $this->dev_profile_id,
            'pricing_type' => $this->pricing_type?->value,
            'pricing_type_label' => $this->pricing_type?->label(),
            /**
             * Valor total do serviço. No orçamento por hora, é o valor da hora vezes as horas estimadas
             */
            'total_price' => $this->total_price,
            /**
             * Só no orçamento por hora (`null` no preço fechado)
             */
            'hourly_rate' => $this->hourly_rate,
            /**
             * Só no orçamento por hora (`null` no preço fechado)
             */
            'estimated_hours' => $this->estimated_hours,
            'message' => $this->message,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'dev_profile' => $this->whenLoaded('devProfile', fn() => $this->devProfile ? new DevProfileSummaryResource($this->devProfile) : null),
            'freelance_job_vacancy' => $this->whenLoaded('freelanceJobVacancy', fn() => $this->freelanceJobVacancy ? new FreelanceJobVacancyResource($this->freelanceJobVacancy) : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
