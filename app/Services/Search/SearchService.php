<?php

namespace App\Services\Search;

use App\Models\ClientProfile;
use App\Models\CompanyProfile;
use App\Models\DevProfile;
use App\Models\JobVacancy;

class SearchService {

    public function search(array $data) {

        $search = $data['search'];
        $limit = $data['limit'] ?? 10;

        return [
            'dev_profiles' => $this->searchDevProfiles($search, $limit),
            'company_profiles' => $this->searchCompanyProfiles($search, $limit),
            'client_profiles' => $this->searchClientProfiles($search, $limit),
            'job_vacancies' => $this->searchJobVacancies($search, $limit),
        ];

    }

    public function topCompanies(int $limit = 10) {

        return CompanyProfile::query()
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

    }

    public function topDevs(int $limit = 10) {

        return DevProfile::query()
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

    }

    public function topClients(int $limit = 10) {

        return ClientProfile::query()
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

    }

    public function topJobVacancies(int $limit = 10) {

        return JobVacancy::query()->with(['softSkill', 'languages'])
            ->withCount('devProfiles')
            ->orderByDesc('dev_profiles_count')
            ->limit($limit)
            ->get();

    }

    private function searchDevProfiles(string $search, int $limit) {

        return DevProfile::query()
            ->where(function($query) use ($search) {
                $query->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('bio', 'ILIKE', "%{$search}%")
                ->orWhere('specialty', 'ILIKE', "%{$search}%")
                ->orWhere('seniority_level', 'ILIKE', "%{$search}%");
            })
            ->limit($limit)
            ->get();

    }

    private function searchCompanyProfiles(string $search, int $limit) {

        return CompanyProfile::query()
            ->where(function($query) use ($search) {
                $query->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('bio', 'ILIKE', "%{$search}%")
                ->orWhere('operational_segment', 'ILIKE', "%{$search}%");
            })
            ->limit($limit)
            ->get();

    }

    private function searchClientProfiles(string $search, int $limit) {

        return ClientProfile::query()
            ->where(function($query) use ($search) {
                $query->where('name', 'ILIKE', "%{$search}%")
                ->orWhere('bio', 'ILIKE', "%{$search}%");
            })
            ->limit($limit)
            ->get();

    }

    private function searchJobVacancies(string $search, int $limit) {

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
            ->limit($limit)
            ->get();

    }

}
