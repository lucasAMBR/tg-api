<?php

namespace App\Http\Resources\ProjectHistory;

use App\Enums\TranslationStatusEnum;
use App\Http\Resources\Language\LanguageResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectHistoryResource extends JsonResource
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
            'translation_status' => $this->translation_status,
            'translation_status_label' => TranslationStatusEnum::labelFromValue($this->translation_status),
            'prod_url' => $this->prod_url,
            'github_url' => $this->github_url,
            'dev_profile_id' => $this->dev_profile_id,
            'dev_profile' => $this->whenLoaded('dev_profile', function () {
                return new DevProfileResource($this->dev_profile);
            }),
            'languages' => $this->whenLoaded('languages', function () {
                return LanguageResource::collection($this->languages);
            }),
            'gallery' => $this->getMedia('gallery')->map(function ($media) {
                $originalRelative = (string) str_replace(config('app.url') . '/storage', '', $media->getUrl());
                $thumbRelative = (string) str_replace(config('app.url') . '/storage', '', $media->getUrl('thumb'));

                return [
                    'id' => (int) $media->id,
                    'original_url' => $originalRelative,
                    'thumb_url' => $thumbRelative,
                ];
            })->values(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
