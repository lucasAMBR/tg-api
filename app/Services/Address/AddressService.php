<?php

namespace App\Services\Address;

use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\Addresses\AddressResource;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AddressService
{
    public function store(array $data): AddressResource
    {


        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        $profile = ProfileHelper::getUserProfileByRole($authUser);

        $address = $this->searchAddressInApi($data['cep']);

        return DB::transaction(function () use ($profile, $address, $data): AddressResource {
            $profile->address()->updateOrCreate([], [
                'cep' => $address['cep'],
                'address_type' => $address['address_type'],
                'street' => $address['address_name'],
                'street_complete_name' => $address['address'],
                'district' => $address['district'],
                'city' => $address['city'],
                'state' => $address['state'],
                'latitude' => $address['lat'],
                'longitude' => $address['lng'],
                'number' => $data['number'],
                'complement' => $data['complement'] ?? null
            ]);

            return new AddressResource($profile->address);
        });
    }

    public function update(array $data, Address $address): AddressResource
    {
        if(!empty($data['cep'])){
            $new_address_data = $this->searchAddressInApi($data['cep']);

            $data['cep'] = $new_address_data['cep'];
            $data['address_type'] = $new_address_data['address_type'];
            $data['street'] = $new_address_data['address_name'];
            $data['street_complete_name'] = $new_address_data['address'];
            $data['district'] = $new_address_data['district'];
            $data['city'] = $new_address_data['city'];
            $data['state'] = $new_address_data['state'];
            $data['latitude'] = $new_address_data['lat'];
            $data['longitude'] = $new_address_data['lng'];
        }

        return DB::transaction(function () use ($data, $address): AddressResource {
            $address->update($data);

            return new AddressResource($address);
        });
    }


    public function delete(Address $address): void
    {
        DB::transaction(function () use ($address) {
            $address->delete();
        });
    }

    private function searchAddressInApi(string $cep): array
    {
        $address = Http::get(env('AWESOME_API_URL') . $cep . "?token=" . env('AWESOME_API_KEY'));

        if ($address->failed()) {
            $errorMessage = $address->json('message')
                            ?? $address->json('error')
                            ?? $address->body()
                            ?? "HTTP Status: " . $address->status();

            throw new ApiException("Error while searching address data: " . $errorMessage);
        }

    return $address->json();
    }
}
