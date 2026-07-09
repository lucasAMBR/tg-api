<?php

namespace App\Http\Resources\ProficiencyTest;

use App\Http\Resources\Question\QuestionResource;
use App\Http\Resources\Question\QuestionResponseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProficiencyTestResponseResource extends JsonResource
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
            'proficiency_test_id' => $this->proficiency_test_id,
            'proficiency_test' => $this->whenLoaded('proficiencyTest', function () {
                return new ProficiencyTestResource($this->proficiencyTest);
            }),
            'question_id' => $this->question_id,
            'question' => $this->whenLoaded('question', function () {
                return new QuestionResource($this->question);
            }),
            'question_response_id' => $this->question_response_id,
            'question_response' => $this->whenLoaded('questionResponse', function () {
                return new QuestionResponseResource($this->questionResponse);
            }),
            'time_taken' => $this->time_taken,
            'alt_tabs_used' => $this->alt_tabs_used,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
