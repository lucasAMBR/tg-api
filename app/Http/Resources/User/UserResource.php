<?php

namespace App\Http\Resources\User;

use App\Http\Resources\Profiles\AdminProfile\AdminProfileResource;
use App\Http\Resources\Profiles\ClientProfile\ClientProfileResource;
use App\Http\Resources\Profiles\CompanyProfile\CompanyProfileResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
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
        return [
            'id' => $this->id,
            'profile_pic' => $this->profile_pic,
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
            'admin_profile' => $this->whenLoaded('admin_profile', function () {
                return new AdminProfileResource($this->admin_profile);
            }),
            'role' => $this->roleNames(),
            'admin_active_profile' => $this->admin_active_profile,
            'is_blocked' => (bool) $this->is_blocked,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Nomes das roles do usuário.
     *
     * O retorno de `getRoleNames()` é uma Collection que o Scramble não
     * consegue tipar (a annotation da relation `roles` acaba vazando para a
     * spec), então normalizamos para uma lista de strings.
     *
     * @return list<string>
     */
    private function roleNames(): array
    {
        $names = [];

        foreach ($this->getRoleNames() as $name) {
            $names[] = (string) $name;
        }

        return $names;
    }
}
