<?php

namespace App\Services\Search;

use App\Http\Resources\JobVacancy\JobVacancyResource;
use App\Http\Resources\Profiles\ClientProfile\ClientProfileResource;
use App\Http\Resources\Profiles\CompanyProfile\CompanyProfileResource;
use App\Http\Resources\Profiles\DevProfile\DevProfileResource;
use App\Http\Resources\Search\SearchResultResource;
use App\Models\ClientProfile;
use App\Models\CompanyProfile;
use App\Models\DevProfile;
use App\Models\JobVacancy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SearchService {

    public function search(array $data): SearchResultResource {

        $search = $data['search'];
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;

        $queries = [
            'dev_profiles' => $this->devProfilesQuery($search),
            'company_profiles' => $this->companyProfilesQuery($search),
            'client_profiles' => $this->clientProfilesQuery($search),
            'job_vacancies' => $this->jobVacanciesQuery($search),
        ];

        $results = array_map(
            fn (Builder $query): LengthAwarePaginator => $query->paginate($perPage, ['*'], 'page', $page),
            $queries
        );

        return new SearchResultResource($results);

    }

    public function topCompanies(int $limit = 10): AnonymousResourceCollection {

        $companies = CompanyProfile::query()
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

        return CompanyProfileResource::collection($companies);

    }

    public function topDevs(int $limit = 10): AnonymousResourceCollection {

        $devs = DevProfile::query()
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

        return DevProfileResource::collection($devs);

    }

    public function topClients(int $limit = 10): AnonymousResourceCollection {

        $clients = ClientProfile::query()
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

        return ClientProfileResource::collection($clients);

    }

    public function topJobVacancies(int $limit = 10): AnonymousResourceCollection {

        $jobVacancies = JobVacancy::query()->with(['softSkill', 'languages'])
            ->withCount('devProfiles')
            ->orderByDesc('dev_profiles_count')
            ->limit($limit)
            ->get();

        return JobVacancyResource::collection($jobVacancies);

    }

    private function devProfilesQuery(string $search): Builder {

        return DevProfile::query()
            ->where(function($query) use ($search) {
                $query->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('bio', 'ILIKE', "%{$search}%")
                ->orWhere('specialty', 'ILIKE', "%{$search}%")
                ->orWhere('seniority_level', 'ILIKE', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->orderBy('id');

    }

    private function companyProfilesQuery(string $search): Builder {

        return CompanyProfile::query()
            ->where(function($query) use ($search) {
                $query->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('bio', 'ILIKE', "%{$search}%")
                ->orWhere('operational_segment', 'ILIKE', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->orderBy('id');

    }

    private function clientProfilesQuery(string $search): Builder {

        return ClientProfile::query()
            ->where(function($query) use ($search) {
                $query->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('bio', 'ILIKE', "%{$search}%");
            })
            ->orderByDesc('created_at')
            ->orderBy('id');

    }

    private function jobVacanciesQuery(string $search): Builder {

        return JobVacancy::query()->with(['softSkill', 'languages'])
            ->where(function($query) use ($search) {
                $query->where('title', 'ILIKE', "%{$search}%")
                ->orWhere('description', 'ILIKE', "%{$search}%")
                ->orWhere('contract_type', 'ILIKE', "%{$search}%")
                ->orWhere('seniority_level', 'ILIKE', "%{$search}%")
                ->orWhereHas('languages', function($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%");
                })
                ->orWhereHas('softSkill', function($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->orderBy('id');

    }

}
