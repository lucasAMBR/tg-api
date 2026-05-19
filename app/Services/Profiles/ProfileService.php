<?php

namespace App\Services\Profiles;

use App\Events\DevProfileCreated;
use App\Exceptions\ApiException;
use App\Http\Resources\Language\LanguageCollection;
use App\Http\Resources\Language\LanguageResource;
use App\Http\Resources\Profiles\ClientProfile\ClientProfileResource;
use App\Http\Resources\Profiles\CompanyProfile\CompanyProfileResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use App\Listeners\CreateRecommendationPreference;
use App\Models\ClientProfile;
use App\Models\CompanyProfile;
use App\Models\DevProfile;
use App\Models\RecommendationPreference;
use Faker\Provider\Company;
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
                'name' => $data['name'],
                'bio' => $data['bio'],
                'phone' => $data['phone'],
                'cpf' => $data['cpf'],
                'birthdate' => $data['birthdate'],
                'open_to_relocation' => $data['open_to_relocation'] ?? false,
                'open_to_work' => $data['open_to_work'] ?? true,
                'score' => $data['score'] ?? 0,
                'seniority_level' => $data['seniority_level'],
            ]);

            event(new DevProfileCreated($devProfile));

            return new DevProfileResource($devProfile);
        });
    }

    public function storeCompanyProfile(Array $data)
    {
        $authUser = Auth::user();

        return DB::transaction(function () use ($authUser, $data) {
            $companyProfile = CompanyProfile::create([
                'user_id' => $authUser->id,
                'name' => $data['name'],
                'bio' => $data['bio'],
                'phone' => $data['phone'],
                'cnpj' => $data['cnpj'],
                'score' => $data['score'] ?? 0,
                'founding_date' => $data['founding_date'],
                'operational_segment' => $data['operational_segment'],
            ]);

            return new CompanyProfileResource($companyProfile);
        });
    }

    public function syncCompanyProfileStacks(CompanyProfile $company, Array $data)
    {
        $authUser = Auth::user();

        if($authUser->id !== $company->user_id){
            throw new ApiException("You cannot sync someone company stack!");
        }

        $languageList = $data['languages'];

        $company->languages()->sync($languageList);

        return new CompanyProfileResource($company);
    }

    public function getCompanyStack(CompanyProfile $company)
    {
        return LanguageResource::collection($company->languages);
    }

    public function storeClientProfile(Array $data)
    {
        $authUser = Auth::user();

        return DB::transaction(function () use ($authUser, $data) {
            $clientProfile = ClientProfile::create([
                'user_id' => $authUser->id,
                'name' => $data['name'],
                'bio' => $data['bio'],
                'phone' => $data['phone'],
                'cpf' => $data['cpf'],
                'score' => $data['score'] ?? 0,
                'birthdate' => $data['birthdate'],
            ]);

            return new ClientProfileResource($clientProfile);
        });
    }

    public function updateDevProfile(Array $data, DevProfile $dev) {

        return DB::transaction(function() use ($data, $dev) {

            $dev->update($data);

            return new DevProfileResource($dev);

        });

    }

    public function updateCompanyProfile(Array $data, CompanyProfile $company) {

        return DB::transaction(function() use ($data, $company) {

            $company->update($data);

            return new CompanyProfileResource($company);

        });

    }

    public function updateClientProfile(Array $data, ClientProfile $client) {

        return DB::transaction(function() use ($data, $client) {

            $client->update($data);

            return new ClientProfileResource($client);

        });

    }

    public function destroyDevProfile(DevProfile $dev) {

        return DB::transaction(function() use ($dev) {

            return $dev->delete();

        });

    }

    public function destroyCompanyProfile(CompanyProfile $company) {

        return DB::transaction(function() use ($company) {

            return $company->delete();

        });

    }

    public function destroyClientProfile(ClientProfile $client) {


        return DB::transaction(function() use ($client) {

            return $client->delete();

        });

    }

}
