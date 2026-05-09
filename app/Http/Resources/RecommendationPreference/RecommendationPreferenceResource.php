<?php

namespace App\Http\Resources\RecommendationPreference;

use App\Http\Resources\Language\LanguageResource;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecommendationPreferenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'allow_clt' => $this->allow_clt,
            'allow_contractor' => $this->allow_contractor,
            'allow_internship'  => $this->allow_internship,
            'allow_on_site' => $this->allow_on_site,
            'allow_hybrid' => $this->allow_hybrid,
            'allow_remote' => $this->allow_remote,
            'on_site_job_radius' => $this->on_site_job_radius,
            'hybrid_jobs_radius' => $this->hybrid_jobs_radius,
            'allow_stack_flexibility' => $this->allow_stack_flexibility,
            'min_remuneration' => (float) $this->min_remuneration,
            'blackListedLanguages' => $this->whenLoaded('blackListedLanguages', function () {
                return LanguageResource::collection($this->blackListedLanguages);
            }),
            'dev_profile_id' => $this->dev_profile_id
        ];
    }
}
