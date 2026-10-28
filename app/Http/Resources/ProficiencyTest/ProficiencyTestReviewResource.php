<?php

namespace App\Http\Resources\ProficiencyTest;

use App\Http\Resources\Question\QuestionResource;
use App\Http\Resources\Question\QuestionResponseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProficiencyTestReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $correctResponse = $this->question?->question_responses->firstWhere('is_correct', true);

        return [
            'question' => new QuestionResource($this->question),
            'dev_response' => $this->questionResponse
                ? new QuestionResponseResource($this->questionResponse)
                : null,
            'is_dev_response_correct' => (bool) $this->questionResponse?->is_correct,
            $this->mergeWhen(! $request->user()?->hasRole('dev'), [
                'correct_response' => $correctResponse
                    ? new QuestionResponseResource($correctResponse)
                    : null,
            ]),
        ];
    }
}
