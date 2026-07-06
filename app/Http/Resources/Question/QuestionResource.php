<?php

namespace App\Http\Resources\Question;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
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
            'question' => $this->question,
            'question_pt' => $this->question_pt,
            'question_en' => $this->question_en,
            'translation_status' => $this->translation_status,
            'difficulty_level' => $this->difficulty_level,
            'language_id' => $this->language_id,
            'category' => $this->category,
            'ideal_time_to_solve' => $this->ideal_time_to_solve,
            'code_snippet' => $this->code_snippet,
            'is_multiple_choice' => $this->is_multiple_choice,
            'seniority_level' => $this->seniority_level,
            'responses' => QuestionResponseResource::collection($this->question_responses),
        ];
    }
}
