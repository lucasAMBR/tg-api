<?php

namespace App\Http\Resources\Addresses;

use App\Http\Resources\Profiles\ClientProfile\ClientProfileResource;
use App\Http\Resources\Profiles\CompanyProfile\CompanyProfileResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use App\Models\ClientProfile;
use App\Models\DevProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            'cep' => $this->cep,
            'address_type' => $this->address_type,
            'street' => $this->street,
            'street_complete_name' => $this->street_complete_name,
            'district' => $this->district,
            'city' => $this->city,
            'state' => $this->state,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'number' => $this->number,
            'complement' => $this->complement,
            'addressable_id' => $this->addressable_id,
            'addressable' => $this->whenLoaded('addressable', function () {
                if($this->addressable instanceof DevProfile){
                    return new DevProfileResource($this->addressable);
                }else if ($this->addressable instanceof ClientProfile){
                    return new ClientProfileResource($this->addressable);
                }else{
                    return new CompanyProfileResource($this->addressable);
                }
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
