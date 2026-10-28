<?php

namespace App\Http\Resources\LanguageAssessment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageAssessmentQuestionOptionResource extends JsonResource
{
    /**
     * O gabarito (`is_correct`) só entra no payload de quem pode vê-lo: a empresa
     * sempre, o dev só depois de entregar a prova. O flag é passado de cima, pelo
     * recurso da prova, em vez de ser decidido aqui
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
            'option' => $this->option,
            'order' => $this->order,
            /**
             * Indica se essa é a alternativa certa. Só vem para a empresa e para o dev
             * que já entregou a prova
             */
            'is_correct' => $this->when($this->withAnswerKey, fn() => $this->is_correct),
            'language_assessment_question_id' => $this->language_assessment_question_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
