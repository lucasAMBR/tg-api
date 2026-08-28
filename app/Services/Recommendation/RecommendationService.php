<?php

namespace App\Services\Recommendation;

use App\Enums\ContractType;
use App\Enums\EmploymentType;
use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\Recommendation\RecommendedDevResource;
use App\Models\CompanyProfile;
use App\Models\DevProfile;
use App\Models\JobVacancy;
use App\Models\JobVacancyEmbedding;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecommendationService {

    public function recommendDevsForJobVacancy(array $data): AnonymousResourceCollection
    {

        $jobVacancy = JobVacancy::findOrFail($data['job_vacancy_id']);

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        if(!$authUser->hasRole('company')) {
            throw new ApiException("You must be a company to get recommendations!");
        }

        $companyProfile = ProfileHelper::getUserProfileByRole($authUser);

        if(!$companyProfile || $jobVacancy->company_profile_id !== $companyProfile->id) {
            throw new ApiException("This job vacancy doesn't belong to your company!");
        }

        $vector = JobVacancyEmbedding::query()
            ->where('job_vacancy_id', $jobVacancy->id)
            ->value('embedding');

        if(!$vector) {
            throw new ApiException("This job vacancy doesn't have an embedding yet!");
        }

        // O binding precisa ir como literal "[1,2,3]": array vira 1536 bindings e desalinha o resto da query
        $vectorLiteral = is_array($vector) ? '[' . implode(',', $vector) . ']' : $vector;

        $limit = $data['limit'] ?? 10;
        $minSimilarity = $data['min_similarity'] ?? null;

        // 1 - distância de cosseno (<=>) = similaridade (1 = idêntico)
        $devs = DevProfile::query()
            ->join('dev_profile_embeddings', 'dev_profile_embeddings.dev_profile_id', '=', 'dev_profiles.id')
            // Devs sem preferência salva caem nos defaults da migration via COALESCE
            ->leftJoin('recommendation_preferences', function(JoinClause $join) {
                $join->on('recommendation_preferences.dev_profile_id', '=', 'dev_profiles.id')
                    ->whereNull('recommendation_preferences.deleted_at');
            })
            ->selectRaw(
                'dev_profiles.*, 1 - (dev_profile_embeddings.embedding <=> ?::vector) AS similarity',
                [$vectorLiteral]
            )
            ->tap(function(Builder $query) use ($jobVacancy, $companyProfile) {
                $this->applyRecommendationPreferences($query, $jobVacancy, $companyProfile);
            })
            ->when(!is_null($minSimilarity), function($query) use ($vectorLiteral, $minSimilarity) {
                $query->whereRaw(
                    '1 - (dev_profile_embeddings.embedding <=> ?::vector) >= ?',
                    [$vectorLiteral, $minSimilarity]
                );
            })
            // Ordena pela distância crua para o pgvector poder usar índice
            ->orderByRaw('dev_profile_embeddings.embedding <=> ?::vector', [$vectorLiteral])
            ->limit($limit)
            ->get();

        return RecommendedDevResource::collection($devs);

    }

    /**
     * Remove do resultado os devs cujas preferências de recomendação são incompatíveis com a vaga.
     */
    private function applyRecommendationPreferences(Builder $query, JobVacancy $jobVacancy, CompanyProfile $companyProfile): void
    {
        $requiredLanguageIds = $jobVacancy->languages()->pluck('languages.id');

        $this->filterByContractType($query, $jobVacancy);
        $this->filterByEmploymentType($query, $jobVacancy);
        $this->filterByMinRemuneration($query, $jobVacancy);
        $this->filterByBlackListedLanguages($query, $requiredLanguageIds);
        $this->filterByStackFlexibility($query, $requiredLanguageIds);
        $this->filterByDistance($query, $jobVacancy, $companyProfile);
    }

    private function filterByContractType(Builder $query, JobVacancy $jobVacancy): void
    {
        [$column, $default] = match($jobVacancy->contract_type) {
            ContractType::CLT => ['allow_clt', 'true'],
            ContractType::CONTRACTOR => ['allow_contractor', 'true'],
            ContractType::INTERNSHIP => ['allow_internship', 'false'],
        };

        $query->whereRaw("COALESCE(recommendation_preferences.{$column}, {$default}) = true");
    }

    private function filterByEmploymentType(Builder $query, JobVacancy $jobVacancy): void
    {
        $column = match($jobVacancy->employment_type) {
            EmploymentType::ON_SITE => 'allow_on_site',
            EmploymentType::HYBRID => 'allow_hybrid',
            EmploymentType::REMOTE => 'allow_remote',
        };

        $query->whereRaw("COALESCE(recommendation_preferences.{$column}, true) = true");
    }

    private function filterByMinRemuneration(Builder $query, JobVacancy $jobVacancy): void
    {
        $query->where(function(Builder $query) use ($jobVacancy) {
            $query->whereNull('recommendation_preferences.min_remuneration')
                ->orWhere('recommendation_preferences.min_remuneration', '<=', $jobVacancy->estimated_salary);
        });
    }

    /**
     * @param Collection<int, string> $requiredLanguageIds
     */
    private function filterByBlackListedLanguages(Builder $query, Collection $requiredLanguageIds): void
    {
        if($requiredLanguageIds->isEmpty()) {
            return;
        }

        $query->whereNotExists(function(QueryBuilder $subQuery) use ($requiredLanguageIds) {
            $subQuery->select(DB::raw(1))
                ->from('recommendation_preferences_black_list')
                ->whereColumn(
                    'recommendation_preferences_black_list.recommendation_preference_id',
                    'recommendation_preferences.id'
                )
                ->whereIn('recommendation_preferences_black_list.language_id', $requiredLanguageIds);
        });
    }

    /**
     * Sem flexibilidade de stack, o dev só aceita vagas que pedem alguma linguagem que ele já domina.
     *
     * @param Collection<int, string> $requiredLanguageIds
     */
    private function filterByStackFlexibility(Builder $query, Collection $requiredLanguageIds): void
    {
        if($requiredLanguageIds->isEmpty()) {
            return;
        }

        $query->where(function(Builder $query) use ($requiredLanguageIds) {
            $query->whereRaw('COALESCE(recommendation_preferences.allow_stack_flexibility, true) = true')
                ->orWhereExists(function(QueryBuilder $subQuery) use ($requiredLanguageIds) {
                    $subQuery->select(DB::raw(1))
                        ->from('hard_skills')
                        ->whereColumn('hard_skills.dev_profile_id', 'dev_profiles.id')
                        ->whereNull('hard_skills.deleted_at')
                        ->whereIn('hard_skills.language_id', $requiredLanguageIds);
                });
        });
    }

    /**
     * Distância só importa quando a vaga exige presença: on site e híbrida. Vaga remota ignora localização,
     * assim como o dev aberto a realocação.
     */
    private function filterByDistance(Builder $query, JobVacancy $jobVacancy, CompanyProfile $companyProfile): void
    {
        $radius = match($jobVacancy->employment_type) {
            EmploymentType::ON_SITE => ['on_site_job_radius', 20],
            EmploymentType::HYBRID => ['hybrid_jobs_radius', 40],
            EmploymentType::REMOTE => null,
        };

        if(is_null($radius)) {
            return;
        }

        [$radiusColumn, $defaultRadius] = $radius;

        $companyAddress = $companyProfile->address;

        if(!$companyAddress) {
            throw new ApiException("Your company must have an address to get recommendations for on site or hybrid job vacancies!");
        }

        $devMorphClass = (new DevProfile)->getMorphClass();

        $query->where(function(Builder $query) use ($companyAddress, $radiusColumn, $defaultRadius, $devMorphClass) {
            // Dev aberto a realocação aceita qualquer distância; sem endereço cadastrado ele fica de fora,
            // porque não dá para provar que está dentro do raio
            $query->whereRaw('COALESCE(dev_profiles.open_to_relocation, false) = true')
                ->orWhereExists(function(QueryBuilder $subQuery) use ($companyAddress, $radiusColumn, $defaultRadius, $devMorphClass) {
                    $subQuery->select(DB::raw(1))
                        ->from('addresses')
                        ->whereColumn('addresses.addressable_id', 'dev_profiles.id')
                        ->where('addresses.addressable_type', $devMorphClass)
                        ->whereNull('addresses.deleted_at')
                        ->whereRaw(
                            $this->distanceInKilometersExpression()
                                . " <= COALESCE(recommendation_preferences.{$radiusColumn}, {$defaultRadius})",
                            [$companyAddress->latitude, $companyAddress->longitude, $companyAddress->latitude]
                        );
                });
        });
    }

    /**
     * Haversine entre o endereço da empresa (bindings: latitude, longitude, latitude) e o endereço do dev.
     * LEAST/GREATEST protegem o acos de estourar o domínio [-1, 1] por erro de arredondamento.
     */
    private function distanceInKilometersExpression(): string
    {
        return '6371 * acos(LEAST(1, GREATEST(-1,
            cos(radians(?)) * cos(radians(addresses.latitude::double precision))
                * cos(radians(addresses.longitude::double precision) - radians(?))
            + sin(radians(?)) * sin(radians(addresses.latitude::double precision))
        )))';
    }

}
