<?php

namespace App\Http\Resources\ScreeningQuestionnaire;

use App\Enums\TranslationStatusEnum;
use App\Http\Resources\JobVacancy\JobVacancyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScreeningQuestionnaireResource extends JsonResource
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
            'translation_status' => $this->translation_status,
            'translation_status_label' => TranslationStatusEnum::labelFromValue($this->translation_status),
            'job_vacancy_id' => $this->job_vacancy_id,
            'vacancy' => new JobVacancyResource($this->whenLoaded('jobVacancy')),
            'questions' => ScreeningQuestionResource::collection($this->whenLoaded('questions')),
            /**
             * Quantos candidatos já responderam o questionário
             */
            'dev_questionnaires_count' => $this->whenCounted('devQuestionnaires'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
