<?php

namespace App\Http\Resources\CompanyProject;

use App\Http\Resources\Language\LanguageResource;
use App\Http\Resources\Profiles\CompanyProfile\CompanyProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'company_profile_id' => $this->company_profile_id,
            'company_profile' => $this->whenLoaded('company_profile', function() {
                return new CompanyProfileResource($this->company_profile);
            }),
            'languages' => $this->whenLoaded('languages', function() {
                return LanguageResource::collection($this->languages);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
