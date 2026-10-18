<?php

namespace App\Http\Resources\LanguageAssessment;

use App\Http\Resources\JobVacancy\JobVacancyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageAssessmentResource extends JsonResource
{
    /**
     * Ponto de entrada do gabarito: quem monta o recurso decide se ele vai junto.
     * A empresa sempre recebe; o dev só depois de entregar a prova
     */
    public function __construct($resource, protected bool $withAnswerKey = false)
    {
        parent::__construct($resource);
    }

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
            'description' => $this->description,
            /**
             * Idioma avaliado, escrito pela empresa (ex.: "Inglês", "Español"). O
             * conteúdo da prova não é traduzido: ela é escrita no idioma avaliado
             */
            'language' => $this->language,
            /**
             * Nota mínima de aprovação. `null` quando a empresa não definiu corte — a
             * prova pontua, mas não dá veredito
             */
            'passing_score' => $this->passing_score,
            /**
             * Nota máxima possível: a soma do peso de todas as perguntas
             */
            'max_score' => $this->whenLoaded('questions', fn() => (int) $this->questions->sum('weight')),
            /**
             * Prazo dado pela empresa para os candidatos responderem
             */
            'due_date' => $this->due_date,
            'job_vacancy_id' => $this->job_vacancy_id,
            'vacancy' => new JobVacancyResource($this->whenLoaded('jobVacancy')),
            'questions' => $this->whenLoaded(
                'questions',
                fn() => $this->questions->map(
                    fn($question) => new LanguageAssessmentQuestionResource($question, $this->withAnswerKey)
                )
            ),
            /**
             * Quantos candidatos já receberam a prova
             */
            'dev_assessments_count' => $this->whenCounted('devAssessments'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
