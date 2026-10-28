<?php

namespace App\Http\Resources\DevJobVacancyLiveCoding;

use App\Http\Resources\DevJobVacancy\DevJobVacancyResource;
use App\Http\Resources\JobVacancy\JobVacancyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevJobVacancyLiveCodingResource extends JsonResource
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
            'title' => $this->title,
            /**
             * Enunciado do exercício, escrito pela empresa. Vem `null` enquanto ela
             * não publica o exercício
             */
            'statement' => $this->statement,
            /**
             * Linguagem/stack esperada na resolução, quando a empresa define uma
             */
            'programming_language' => $this->programming_language,
            'scheduled_at' => $this->scheduled_at,
            'duration_in_minutes' => $this->duration_in_minutes,
            'status' => $this->status,
            'status_label' => $this->status?->label(),
            /**
             * Preenchidos quando os participantes entram/saem da call, indicando
             * se ela já começou e/ou terminou de fato
             */
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'job_vacancy_id' => $this->job_vacancy_id,
            'vacancy' => new JobVacancyResource($this->whenLoaded('jobVacancy')),
            'dev_job_vacancy_id' => $this->dev_job_vacancy_id,
            /**
             * Candidatura, com o perfil do dev carregado
             */
            'apply' => new DevJobVacancyResource($this->whenLoaded('devJobVacancy')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
