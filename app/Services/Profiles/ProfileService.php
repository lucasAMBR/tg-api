<?php

namespace App\Services\Profiles;

use App\Events\DevProfileCreated;
use App\Exceptions\ApiException;
use App\Http\Resources\Language\LanguageResource;
use App\Http\Resources\Profiles\ClientProfile\ClientProfileCollection;
use App\Http\Resources\Profiles\ClientProfile\ClientProfileResource;
use App\Http\Resources\Profiles\CompanyProfile\CompanyProfileCollection;
use App\Http\Resources\Profiles\CompanyProfile\CompanyProfileResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileCollection;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use App\Jobs\GenerateDevProfileEmbeddingJob;
use App\Jobs\TranslateContentJob;
use App\Models\ClientProfile;
use App\Models\CompanyProfile;
use App\Models\DevProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProfileService
{

    public function indexDevProfiles(array $data)
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $seniorityLevel = $data['seniority_level'] ?? null;
        $specialty = $data['specialty'] ?? null;
        $openToRelocation = $data['open_to_relocation'] ?? null;
        $openToWork = $data['open_to_work'] ?? null;

        $devProfiles = DevProfile::query()
            ->with(['address', 'user.media'])
            ->when($search, function(Builder $query, $search) {
                $query->where('name', 'ILIKE', "%{$search}%");
            })
            ->when($seniorityLevel, function(Builder $query, $seniorityLevel) {
                $query->where('seniority_level', $seniorityLevel);
            })
            ->when($specialty, function(Builder $query, $specialty) {
                $query->where('specialty', $specialty);
            })
            ->when(array_key_exists('open_to_relocation', $data), function(Builder $query) use ($openToRelocation) {
                $query->where('open_to_relocation', $openToRelocation);
            })
            ->when(array_key_exists('open_to_work', $data), function(Builder $query) use ($openToWork) {
                $query->where('open_to_work', $openToWork);
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return new DevProfileCollection($devProfiles);
    }

    public function indexCompanyProfiles(array $data)
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $operationalSegment = $data['operational_segment'] ?? null;

        $companyProfiles = CompanyProfile::query()
            ->with(['address', 'user.media'])
            ->when($search, function(Builder $query, $search) {
                $query->where('name', 'ILIKE', "%{$search}%");
            })
            ->when($operationalSegment, function(Builder $query, $operationalSegment) {
                $query->where('operational_segment', $operationalSegment);
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return new CompanyProfileCollection($companyProfiles);
    }

    public function indexClientProfiles(array $data)
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;

        $clientProfiles = ClientProfile::query()
            ->with(['address', 'user.media'])
            ->when($search, function(Builder $query, $search) {
                $query->where('name', 'ILIKE', "%{$search}%");
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return new ClientProfileCollection($clientProfiles);
    }

    public function showDevProfile(array $data)
    {
        $dev = DevProfile::findOrFail($data['id']);

        $dev->load('user.media');

        return new DevProfileResource($dev);
    }

    public function showCompanyProfile(array $data)
    {
        $company = CompanyProfile::findOrFail($data['id']);

        $company->load('user.media');

        return new CompanyProfileResource($company);
    }

    public function showClientProfile(array $data)
    {
        $client = ClientProfile::findOrFail($data['id']);

        $client->load('user.media');

        return new ClientProfileResource($client);
    }

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
                'specialty' => $data['specialty'],
            ]);

            event(new DevProfileCreated($devProfile));

            TranslateContentJob::dispatch($devProfile);

            GenerateDevProfileEmbeddingJob::dispatchDebounced($devProfile->id);

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

            TranslateContentJob::dispatch($companyProfile);

            return new CompanyProfileResource($companyProfile);
        });
    }

    public function syncCompanyProfileStacks(Array $data)
    {
        $company = CompanyProfile::findOrFail($data['id']);

        $authUser = Auth::user();

        if($authUser->id !== $company->user_id){
            throw new ApiException("You cannot sync someone else's company stack!", 403);
        }

        $languageList = $data['languages'];

        $company->languages()->sync($languageList);

        return new CompanyProfileResource($company);
    }

    public function getCompanyStack(array $data)
    {
        $company = CompanyProfile::findOrFail($data['id']);

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

            TranslateContentJob::dispatch($clientProfile);

            return new ClientProfileResource($clientProfile);
        });
    }

    public function updateDevProfile(Array $data) {

        $dev = DevProfile::findOrFail($data['id']);

        $this->ensureAuthorized('update', $dev);

        $data = Arr::except($data, ['id']);

        return DB::transaction(function() use ($data, $dev) {

            $dev->update($data);

            if (isset($data['bio'])) {
                TranslateContentJob::dispatch($dev);
            }

            if (isset($data['bio']) || isset($data['specialty']) || isset($data['seniority_level'])) {
                GenerateDevProfileEmbeddingJob::dispatchDebounced($dev->id);
            }

            return new DevProfileResource($dev);

        });

    }

    public function updateCompanyProfile(Array $data) {

        $company = CompanyProfile::findOrFail($data['id']);

        $this->ensureAuthorized('update', $company);

        $data = Arr::except($data, ['id']);

        return DB::transaction(function() use ($data, $company) {

            $company->update($data);

            if (isset($data['bio'])) {
                TranslateContentJob::dispatch($company);
            }

            return new CompanyProfileResource($company);

        });

    }

    public function updateClientProfile(Array $data) {

        $client = ClientProfile::findOrFail($data['id']);

        $this->ensureAuthorized('update', $client);

        $data = Arr::except($data, ['id']);

        return DB::transaction(function() use ($data, $client) {

            $client->update($data);

            if (isset($data['bio'])) {
                TranslateContentJob::dispatch($client);
            }

            return new ClientProfileResource($client);

        });

    }

    public function destroyDevProfile(array $data) {

        $dev = DevProfile::findOrFail($data['id']);

        $this->ensureAuthorized('delete', $dev);

        return DB::transaction(function() use ($dev) {

            return $dev->delete();

        });

    }

    public function destroyCompanyProfile(array $data) {

        $company = CompanyProfile::findOrFail($data['id']);

        $this->ensureAuthorized('delete', $company);

        return DB::transaction(function() use ($company) {

            return $company->delete();

        });

    }

    public function destroyClientProfile(array $data) {

        $client = ClientProfile::findOrFail($data['id']);

        $this->ensureAuthorized('delete', $client);

        return DB::transaction(function() use ($client) {

            return $client->delete();

        });

    }


    private function ensureAuthorized(string $ability, DevProfile|CompanyProfile|ClientProfile $profile): void
    {
        if (Gate::denies($ability, $profile)) {
            throw new ApiException('This profile does not belong to you!', 403);
        }
    }

}
