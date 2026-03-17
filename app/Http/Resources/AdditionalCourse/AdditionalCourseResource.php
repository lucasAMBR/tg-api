<?php

namespace App\Http\Resources\AdditionalCourse;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdditionalCourseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $certificate = $this->getFirstMedia('certificate');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'provider' => $this->provider,
            'dev_profile_id' => $this->dev_profile_id,
            'verified' => $this->certificate === null ? false : true,
            'certificate' => $certificate ? [
                'id' => $certificate->id,
                'certificate_url' => str_replace(config('app.url') . '/storage', '', $certificate->getUrl())
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
