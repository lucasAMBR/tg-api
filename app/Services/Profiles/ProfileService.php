<?php

namespace App\Services\Profiles;

use App\Http\Resources\Profiles\ClientProfile\ClientProfileResource;
use App\Http\Resources\Profiles\CompanyProfile\CompanyProfileResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use App\Models\ClientProfile;
use App\Models\CompanyProfile;
use App\Models\DevProfile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    public function storeDevProfile(Array $data)
    {
        $authUser = Auth::user();

        return DB::transaction(function () use ($authUser, $data) {
            $devProfile = DevProfile::create([
                'user_id' => $authUser->id,
                'bio' => $data['bio'],
                'cpf' => $data['cpf'],
                'birthdate' => $data['birthdate'],
                'open_to_relocation' => $data['open_to_relocation'] ?? false,
                'open_to_work' => $data['open_to_work'] ?? true,
                'score' => $data['score'] ?? 0,
                'seniority_level' => $data['seniority_level'],
            ]);

            return new DevProfileResource($devProfile);
        });
    }

    public function storeCompanyProfile(Array $data)
    {
        $authUser = Auth::user();

        return DB::transaction(function () use ($authUser, $data) {
            $companyProfile = CompanyProfile::create([
                'user_id' => $authUser->id,
                'bio' => $data['bio'],
                'cnpj' => $data['cnpj'],
                'score' => $data['score'] ?? 0,
                'founding_date' => $data['founding_date'],
                'operational_segment' => $data['operational_segment'],
            ]);

            return new CompanyProfileResource($companyProfile);
        });
    }

    public function storeClientProfile(Array $data)
    {
        $authUser = Auth::user();

        return DB::transaction(function () use ($authUser, $data) {
            $clientProfile = ClientProfile::create([
                'user_id' => $authUser->id,
                'bio' => $data['bio'],
                'cpf' => $data['cpf'],
                'score' => $data['score'] ?? 0,
                'birthdate' => $data['birthdate'],
            ]);

            return new ClientProfileResource($clientProfile);
        });
    }

    public function updateDevProfile(Array $data, DevProfile $dev) {

        $authUser = Auth::user();

        DB::transaction(function() use ($data, $dev) {

            $dev->update($data);

            return new DevProfileResource($dev);

        });

    }

    public function updateCompanyProfile(Array $data, CompanyProfile $company) {

        $authUser = Auth::user();

        DB::transaction(function() use ($data, $company) {

            $company->update($data);

            return new CompanyProfileResource($company);

        });

    }

    public function updateClientProfile(Array $data, ClientProfile $client) {

        $authUser = Auth::user();

        DB::transaction(function() use ($data, $client) {

            $client->update($data);

            return new CompanyProfileResource($client);

        });

    }

}
