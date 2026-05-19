<?php

namespace App\Http\Resources\Profiles\CompanyProfile;

use App\Enums\OperationalSegmentEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyProfileResource extends JsonResource
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
            'user_id' => $this->user_id,
            'name' => $this->name,
            'bio' => $this->bio,
            'cnpj' => $this->cnpj,
            'phone' => $this->phone,
            'fouding_date' => $this->founding_date,
            'operational_segment' => $this->operational_segment,
            'operational_segment_label' => OperationalSegmentEnum::labelFromValue($this->operational_segment),
            'score' => $this->score,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
