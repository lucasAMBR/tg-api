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
            'id' => $this->id,
            'title' => $this->title,
            'title_pt' => $this->title_pt,
            'title_en' => $this->title_en,
            'description' => $this->description,
            'description_pt' => $this->description_pt,
            'description_en' => $this->description_en,
            'prod_url' => $this->prod_url,
            'github_url' => $this->github_url,
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
