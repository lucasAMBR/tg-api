<?php

namespace App\Http\Resources\DevJobVacancy;

use App\Http\Resources\DevJobVacancyInterview\DevJobVacancyInterviewResource;
use App\Http\Resources\DevScreeningQuestionnaire\DevScreeningQuestionnaireResource;
use App\Http\Resources\JobVacancy\JobVacancyResource;
use App\Http\Resources\PortfolioSolicitation\PortfolioSolicitationResource;
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
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->status?->label(),
            /**
             * Passo atual do processo seletivo. Quando a candidatura é recusada,
             * mantém o passo em que a recusa aconteceu
             */
            'process_step' => $this->process_step,
            'process_step_label' => $this->process_step?->label(),
            'feedback' => $this->feedback,
            'applied_at' => $this->created_at,
            'dev_profile_id' => $this->dev_profile_id, 
            'profile' => new DevProfileResource($this->whenLoaded('devProfile')),
            'job_vacancy_id' => $this->job_vacancy_id,
            'vacancy' => new JobVacancyResource($this->whenLoaded('jobVacancy')),
            /**
             * Solicitação de portfólio da candidatura, carregada na etapa de análise de portfólio
             */
            'portfolio_solicitation' => new PortfolioSolicitationResource($this->whenLoaded('portfolioSolicitation')),
            /**
             * Preenchimento do questionário de triagem da candidatura, carregado na
             * etapa de perguntas de triagem
             */
            'screening_questionnaire' => new DevScreeningQuestionnaireResource($this->whenLoaded('screeningQuestionnaire')),
            /**
             * Entrevista da candidatura, carregada na etapa de entrevista
             */
            'interview' => new DevJobVacancyInterviewResource($this->whenLoaded('interview'))

            /**
             * Caso retorne o model do profile ou da vaga eu preciso chamar o método
             * whenPivotLoaded() e passo a tabela e uma função de retorno sendo retornada com
             * $this->pivot->nome-campo
             */
        ];
    }
}
