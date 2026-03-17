<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Profiles\ClientProfile\ClientProfileResource;
use App\Http\Resources\Profiles\CompanyProfile\CompanyProfileResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $media = $this->getFirstMedia('profile_pic');

        return [
            'id' => $this->id,
            'profile_pic' => $media ? [
                'id'           => $media->id,
                'original_url' => str_replace(config('app.url') . '/storage', '', $media->getUrl()),
                'thumb_url'    => str_replace(config('app.url') . '/storage', '', $media->getUrl('thumb')),
            ] : null,
            'email' => $this->email,
            'dev_profile' => $this->whenLoaded('dev_profile', function () {
                return new DevProfileResource($this->dev_profile);
            }),
            'company_profile' => $this->whenLoaded('company_profile', function () {
                return new CompanyProfileResource($this->company_profile);
            }),
            'client_profile' => $this->whenLoaded('client_profile', function () {
                return new ClientProfileResource($this->client_profile);
            }),
            'role' => $this->getRoleNames(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
