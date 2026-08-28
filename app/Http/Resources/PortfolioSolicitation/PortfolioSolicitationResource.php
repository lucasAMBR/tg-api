<?php

namespace App\Http\Resources\PortfolioSolicitation;

use App\Http\Resources\DevJobVacancy\DevJobVacancyResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortfolioSolicitationResource extends JsonResource
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
            'portfolio_url' => $this->portfolio_url,
            /**
             * Tipo do portfólio, identificado a partir da url enviada pelo desenvolvedor
             */
            'type' => $this->type,
            'type_label' => $this->type?->label(),
            'status' => $this->status,
            'status_label' => $this->status?->label(),
            'due_date' => $this->due_date,
            'dev_job_vacancy_id' => $this->dev_job_vacancy_id,
            'apply' => new DevJobVacancyResource($this->whenLoaded('devJobVacancy')),
            'dev_profile_id' => $this->dev_profile_id,
            'profile' => new DevProfileResource($this->whenLoaded('devProfile')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
