<?php

namespace App\Http\Resources\ProjectHistory;

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
            'description' => $this->description,
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
                $originalRelative = str_replace(config('app.url') . '/storage', '', $media->getUrl());
                $thumbRelative = str_replace(config('app.url') . '/storage', '', $media->getUrl('thumb'));

                return [
                    'id' => $media->id,
                    'original_url' => $originalRelative,
                    'thumb_url' => $thumbRelative,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
