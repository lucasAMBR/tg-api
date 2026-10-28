<?php

namespace App\Http\Resources\TechnicalChallenge;

use App\Enums\TranslationStatusEnum;
use App\Http\Resources\JobVacancy\JobVacancyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TechnicalChallengeResource extends JsonResource
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
            'title_pt' => $this->title_pt,
            'title_en' => $this->title_en,
            'description' => $this->description,
            'description_pt' => $this->description_pt,
            'description_en' => $this->description_en,
            /**
             * Regras da entrega definidas pela empresa
             */
            'rules' => $this->rules,
            'rules_pt' => $this->rules_pt,
            'rules_en' => $this->rules_en,
            'translation_status' => $this->translation_status,
            'translation_status_label' => TranslationStatusEnum::labelFromValue($this->translation_status),
            /**
             * Prazo de entrega definido pela empresa, herdado por cada candidatura que
             * entra na etapa
             */
            'due_date' => $this->due_date,
            /**
             * Indica se o dev pode enviar o projeto compactado além do repositório
             */
            'accepts_zip_submission' => $this->accepts_zip_submission,
            /**
             * Documento guia com o enunciado completo, quando a empresa anexou um
             *
             * @var array{id: int, name: string, path: string, url: string, size: int}|null
             */
            'guide_document' => $this->guide_document,
            'job_vacancy_id' => $this->job_vacancy_id,
            'vacancy' => new JobVacancyResource($this->whenLoaded('jobVacancy')),
            /**
             * Quantos candidatos já receberam o desafio
             */
            'dev_challenges_count' => $this->whenCounted('devChallenges'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
