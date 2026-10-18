<?php

namespace App\Http\Resources\DevLanguageAssessment;

use App\Http\Resources\LanguageAssessment\LanguageAssessmentQuestionOptionResource;
use App\Http\Resources\LanguageAssessment\LanguageAssessmentQuestionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevLanguageAssessmentAnswerResource extends JsonResource
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
             * Texto escrito pelo dev. `null` nas perguntas de escolha
             */
            'response' => $this->response,
            /**
             * Resultado da correção automática desta resposta
             */
            'is_correct' => $this->is_correct,
            'earned_score' => $this->earned_score,
            'language_assessment_question_id' => $this->language_assessment_question_id,
            /**
             * A resposta só existe depois da entrega, então o gabarito da pergunta
             * pode vir junto
             */
            'question' => $this->whenLoaded(
                'question',
                fn() => new LanguageAssessmentQuestionResource($this->question, true)
            ),
            /**
             * Alternativas marcadas pelo dev. Sempre vazia nas perguntas escritas
             */
            'options' => $this->whenLoaded(
                'options',
                fn() => $this->options->map(
                    fn($option) => new LanguageAssessmentQuestionOptionResource($option, true)
                )
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
