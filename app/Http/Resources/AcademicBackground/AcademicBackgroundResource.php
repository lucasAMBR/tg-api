<?php

namespace App\Http\Resources\AcademicBackground;

use App\Enums\DegreeLevelEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicBackgroundResource extends JsonResource
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
            'dev_profile_id' => $this->dev_profile_id,
            'degree' => $this->degree,
            'degree_level' => $this->degree_level,
            'degree_level_label' => DegreeLevelEnum::labelFromValue($this->degree_level),
            'institution' => $this->institution,
            'is_verified' => $this->certificate === null ? false : true,
            'certificate' => $certificate ? [
                'id'              => $certificate->id,
                'certificate_url' => str_replace(config('app.url') . '/storage', '', $certificate->getUrl()),
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
