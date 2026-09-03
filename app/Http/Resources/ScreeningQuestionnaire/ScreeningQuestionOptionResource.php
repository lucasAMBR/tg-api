<?php

namespace App\Http\Resources\ScreeningQuestionnaire;

use App\Enums\TranslationStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScreeningQuestionOptionResource extends JsonResource
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
            'screening_question_id' => $this->screening_question_id,
            'option' => $this->option,
            'option_pt' => $this->option_pt,
            'option_en' => $this->option_en,
            'translation_status' => $this->translation_status,
            'translation_status_label' => TranslationStatusEnum::labelFromValue($this->translation_status),
            'order' => $this->order
        ];
    }
}
