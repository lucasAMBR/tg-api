<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Autor resumido de postagens, comentários e curtidas.
 * Espera as relações de User::PROFILE_RELATIONS carregadas.
 */
class UserSimpleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profiles = [
            'dev' => $this->dev_profile,
            'company' => $this->company_profile,
            'client' => $this->client_profile,
            'admin' => $this->admin_profile,
        ];

        $profileType = array_key_first(array_filter($profiles));
        $profile = $profileType ? $profiles[$profileType] : null;

        return [
            'id' => $this->id,
            'name' => $profile?->name,
            'profile_id' => $profile?->id,
            'profile_type' => $profileType,
            'profile_pic' => $this->profile_pic,
        ];
    }
}
