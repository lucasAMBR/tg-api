<?php

namespace App\Http\Resources\DevJobVacancy;

use App\Http\Resources\JobVacancy\JobVacancyResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevJobVacancyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status,
            'feedback' => $this->feedback,
            'applied_at' => $this->created_at,
            'dev_profile_id' => $this->dev_profile_id, 
            'profile' => new DevProfileResource($this->whenLoaded('devProfile')),
            'job_vacancy_id' => $this->job_vacancy_id,
            'vacancy' => new JobVacancyResource($this->whenLoaded('jobVacancy'))

            /**
             * Caso retorne o model do profile ou da vaga eu preciso chamar o método
             * whenPivotLoaded() e passo a tabela e uma função de retorno sendo retornada com
             * $this->pivot->nome-campo
             */
        ];
    }
}
