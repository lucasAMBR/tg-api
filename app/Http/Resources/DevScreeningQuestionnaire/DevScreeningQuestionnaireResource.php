<?php

namespace App\Http\Resources\DevScreeningQuestionnaire;

use App\Http\Resources\DevJobVacancy\DevJobVacancyResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use App\Http\Resources\ScreeningQuestionnaire\ScreeningQuestionnaireResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevScreeningQuestionnaireResource extends JsonResource
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
             * `pending` enquanto o dev não envia as respostas, `answered` depois do envio
             */
            'status' => $this->status,
            'status_label' => $this->status?->label(),
            /**
             * Prazo dado pela empresa para o dev responder o questionário
             */
            'due_date' => $this->due_date,
            'submitted_at' => $this->submitted_at,
            'screening_questionnaire_id' => $this->screening_questionnaire_id,
            /**
             * Questionário a responder, com as perguntas e as alternativas
             */
            'questionnaire' => new ScreeningQuestionnaireResource($this->whenLoaded('questionnaire')),
            'dev_job_vacancy_id' => $this->dev_job_vacancy_id,
            /**
             * Candidatura, com o perfil do dev carregado
             */
            'apply' => new DevJobVacancyResource($this->whenLoaded('devJobVacancy')),
            'dev_profile_id' => $this->dev_profile_id,
            'profile' => new DevProfileResource($this->whenLoaded('devProfile')),
            /**
             * Respostas enviadas pelo dev, uma por pergunta respondida
             */
            'answers' => DevScreeningAnswerResource::collection($this->whenLoaded('answers')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
