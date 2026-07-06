<?php

namespace App\Http\Resources\Question;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResponseResource extends JsonResource
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
            'question_id' => $this->question_id,
            'response' => $this->response,
            'response_pt' => $this->response_pt,
            'response_en' => $this->response_en,
            'translation_status' => $this->translation_status,
            'is_correct' => $this->is_correct,
            'code_snippet' => $this->code_snippet,
        ];
    }
}
