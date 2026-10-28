<?php

namespace App\Http\Resources\ScreeningQuestionnaire;

use App\Enums\TranslationStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScreeningQuestionResource extends JsonResource
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
            'screening_questionnaire_id' => $this->screening_questionnaire_id,
            'question' => $this->question,
            'question_pt' => $this->question_pt,
            'question_en' => $this->question_en,
            'translation_status' => $this->translation_status,
            'translation_status_label' => TranslationStatusEnum::labelFromValue($this->translation_status),
            /**
             * `essay` (dissertativa), `single_choice` (escolha única) ou
             * `multiple_choice` (múltipla escolha)
             */
            'type' => $this->type,
            'type_label' => $this->type?->label(),
            'is_required' => (bool) $this->is_required,
            'order' => $this->order,
            /**
             * Alternativas da pergunta, vazia nas dissertativas
             */
            'options' => ScreeningQuestionOptionResource::collection($this->whenLoaded('options'))
        ];
    }
}
