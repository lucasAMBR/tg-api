<?php

namespace App\Http\Resources\Profiles\CompanyProfile;

use App\Enums\OperationalSegmentEnum;
use App\Enums\TranslationStatusEnum;
use App\Http\Resources\Addresses\AddressResource;
use App\Http\Resources\User\UserResource;
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
            'user' => new UserResource($this->whenLoaded('user')),
            'profile_pic' => $this->user?->profile_pic['original_url'] ?? null,
            'name' => $this->name,
            'bio' => $this->bio,
            'bio_pt' => $this->bio_pt,
            'bio_en' => $this->bio_en,
            'translation_status' => $this->translation_status,
            'translation_status_label' => TranslationStatusEnum::labelFromValue($this->translation_status),
            'cnpj' => $this->cnpj,
            'phone' => $this->phone,
            'fouding_date' => $this->founding_date,
            'operational_segment' => $this->operational_segment,
            'operational_segment_label' => OperationalSegmentEnum::labelFromValue($this->operational_segment),
            'score' => $this->score,
            'address' => new AddressResource($this->whenLoaded('address')),
            'is_blocked' => (bool) $this->user->is_blocked,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
