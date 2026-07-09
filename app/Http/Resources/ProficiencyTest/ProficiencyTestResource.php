<?php

namespace App\Http\Resources\ProficiencyTest;

use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProficiencyTestResource extends JsonResource
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
            'dev_profile_id' => $this->dev_profile_id,
            'dev_profile' => $this->whenLoaded('devProfile', function () {
                return new DevProfileResource($this->devProfile);
            }),
            'seniority_level' => $this->seniority_level,
            'specialty' => $this->specialty,
            'backend_category' => $this->backend_category,
            'frontend_category' => $this->frontend_category,
            'status' => $this->status,
            'score' => $this->score,
            'max_score' => $this->max_score,
            'solicitation_date' => $this->solicitation_date,
            'proficiency_test_responses' => $this->whenLoaded('proficiencyTestResponses', function () {
                return ProficiencyTestResponseResource::collection($this->proficiencyTestResponses);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
