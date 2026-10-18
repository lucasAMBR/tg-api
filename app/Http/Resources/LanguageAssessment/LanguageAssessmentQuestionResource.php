<?php

namespace App\Http\Resources\LanguageAssessment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageAssessmentQuestionResource extends JsonResource
{
    /**
     * Ver `LanguageAssessmentQuestionOptionResource`: o gabarito desce pelo flag em
     * vez de ser decidido em cada recurso
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
            'question' => $this->question,
            /**
             * `single_choice`, `multiple_choice` (respondidas nas alternativas) ou
             * `text` (respondida escrevendo)
             */
            'type' => $this->type,
            'type_label' => $this->type?->label(),
            /**
             * Quanto a pergunta vale na nota final
             */
            'weight' => $this->weight,
            'order' => $this->order,
            /**
             * Respostas aceitas nas perguntas escritas. Só vem para a empresa e para o
             * dev que já entregou a prova
             *
             * @var array<int, string>|null
             */
            'expected_answers' => $this->when($this->withAnswerKey, fn() => $this->expected_answers),
            'language_assessment_id' => $this->language_assessment_id,
            /**
             * Alternativas da pergunta, na ordem definida pela empresa. Sempre vazia
             * nas perguntas escritas
             */
            'options' => $this->whenLoaded(
                'options',
                fn() => $this->options->map(
                    fn($option) => new LanguageAssessmentQuestionOptionResource($option, $this->withAnswerKey)
                )
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
