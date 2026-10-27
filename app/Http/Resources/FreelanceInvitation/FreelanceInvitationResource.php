<?php

namespace App\Http\Resources\FreelanceInvitation;

use App\Http\Resources\Profiles\DevProfile\DevProfileSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FreelanceInvitationResource extends JsonResource
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
            'freelance_job_vacancy_id' => $this->freelance_job_vacancy_id,
            'dev_profile_id' => $this->dev_profile_id,
            'dev_profile' => $this->whenLoaded('devProfile', fn() => $this->devProfile ? new DevProfileSummaryResource($this->devProfile) : null),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
