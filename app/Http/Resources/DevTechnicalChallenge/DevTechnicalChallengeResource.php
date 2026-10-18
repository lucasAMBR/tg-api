<?php

namespace App\Http\Resources\DevTechnicalChallenge;

use App\Http\Resources\DevJobVacancy\DevJobVacancyResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use App\Http\Resources\TechnicalChallenge\TechnicalChallengeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevTechnicalChallengeResource extends JsonResource
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
            /**
             * `pending` enquanto o dev não entrega a solução, `delivered` depois do envio
             */
            'status' => $this->status,
            'status_label' => $this->status?->label(),
            /**
             * Repositório com a solução, enviado pelo dev
             */
            'repository_url' => $this->repository_url,
            /**
             * Projeto compactado enviado pelo dev, quando o desafio aceita o zip
             *
             * @var array{id: int, name: string, path: string, url: string, size: int}|null
             */
            'submission_file' => $this->submission_file,
            /**
             * Prazo dado pela empresa para o dev entregar o desafio
             */
            'due_date' => $this->due_date,
            'submitted_at' => $this->submitted_at,
            'technical_challenge_id' => $this->technical_challenge_id,
            /**
             * Desafio a entregar, com o enunciado, as regras e o documento guia
             */
            'challenge' => new TechnicalChallengeResource($this->whenLoaded('technicalChallenge')),
            'dev_job_vacancy_id' => $this->dev_job_vacancy_id,
            /**
             * Candidatura, com o perfil do dev carregado
             */
            'apply' => new DevJobVacancyResource($this->whenLoaded('devJobVacancy')),
            'dev_profile_id' => $this->dev_profile_id,
            'profile' => new DevProfileResource($this->whenLoaded('devProfile')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
