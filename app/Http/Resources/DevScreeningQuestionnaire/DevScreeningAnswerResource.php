<?php

namespace App\Http\Resources\DevScreeningQuestionnaire;

use App\Http\Resources\ScreeningQuestionnaire\ScreeningQuestionOptionResource;
use App\Http\Resources\ScreeningQuestionnaire\ScreeningQuestionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevScreeningAnswerResource extends JsonResource
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
            'dev_screening_questionnaire_id' => $this->dev_screening_questionnaire_id,
            'screening_question_id' => $this->screening_question_id,
            'question' => new ScreeningQuestionResource($this->whenLoaded('question')),
            /**
             * Texto da resposta, preenchido só nas perguntas dissertativas
             */
            'response' => $this->response,
            /**
             * Alternativas marcadas, preenchidas só nas perguntas de escolha
             */
            'options' => ScreeningQuestionOptionResource::collection($this->whenLoaded('options')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
