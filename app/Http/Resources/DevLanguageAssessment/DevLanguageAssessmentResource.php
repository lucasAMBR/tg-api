<?php

namespace App\Http\Resources\DevLanguageAssessment;

use App\Http\Resources\DevJobVacancy\DevJobVacancyResource;
use App\Http\Resources\LanguageAssessment\LanguageAssessmentResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevLanguageAssessmentResource extends JsonResource
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
             * `pending` enquanto o dev não entrega a prova, `answered` depois da
             * entrega — que é quando a correção automática acontece
             */
            'status' => $this->status,
            'status_label' => $this->status?->label(),
            /**
             * Resultado da correção, preenchido na entrega
             */
            'score' => $this->score,
            'max_score' => $this->max_score,
            /**
             * Veredito contra a nota de corte da prova, congelado na correção. `null`
             * quando a empresa não definiu `passing_score`
             */
            'passed' => $this->passed,
            /**
             * Prazo dado pela empresa para o dev responder
             */
            'due_date' => $this->due_date,
            'submitted_at' => $this->submitted_at,
            'language_assessment_id' => $this->language_assessment_id,
            /**
             * Prova a responder, com as perguntas e alternativas. O gabarito só vem
             * junto depois da entrega
             */
            'assessment' => $this->whenLoaded(
                'assessment',
                fn() => new LanguageAssessmentResource($this->assessment, $this->isAnswered())
            ),
            'dev_job_vacancy_id' => $this->dev_job_vacancy_id,
            /**
             * Candidatura, com o perfil do dev carregado
             */
            'apply' => new DevJobVacancyResource($this->whenLoaded('devJobVacancy')),
            'dev_profile_id' => $this->dev_profile_id,
            'profile' => new DevProfileResource($this->whenLoaded('devProfile')),
            /**
             * Respostas enviadas pelo dev, uma por pergunta respondida, já corrigidas
             */
            'answers' => DevLanguageAssessmentAnswerResource::collection($this->whenLoaded('answers')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
