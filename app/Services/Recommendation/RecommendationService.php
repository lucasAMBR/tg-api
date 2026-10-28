<?php

namespace App\Services\Recommendation;

use App\Enums\ContractType;
use App\Enums\EmploymentType;
use App\Enums\FreelanceJobVacancyStatusEnum;
use App\Enums\FreelanceProposalStatusEnum;
use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Http\Resources\Recommendation\RecommendedDevResource;
use App\Http\Resources\Recommendation\RecommendedFreelanceDevResource;
use App\Http\Resources\Recommendation\RecommendedFreelanceJobVacancyResource;
use App\Http\Resources\Recommendation\RecommendedFreelanceProposalResource;
use App\Models\CompanyProfile;
use App\Models\DevProfile;
use App\Models\DevProfileEmbedding;
use App\Models\FreelanceJobVacancy;
use App\Models\FreelanceJobVacancyEmbedding;
use App\Models\FreelanceProposal;
use App\Models\JobVacancy;
use App\Models\JobVacancyEmbedding;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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

        return RecommendedDevResource::collection($this->recommendDevsByVector(
            $vectorLiteral,
            $data,
            fn(Builder $query) => $this->applyRecommendationPreferences($query, $jobVacancy, $companyProfile)
        ));

    }

    /**
     * Mesma recomendação por similaridade, mas partindo de uma vaga freelance. Aqui o
     * dono é um perfil de cliente, não de empresa, e entram também os devs que ainda não
     * orçaram: cada um vem marcado com `has_proposal` e `is_invited`, para o cliente
     * convidar quem ainda não orçou.
     */
    public function recommendDevsForFreelanceJobVacancy(array $data): AnonymousResourceCollection
    {

        $freelanceJobVacancy = $this->findClientFreelanceJobVacancy($data['freelance_job_vacancy_id']);

        $vectorLiteral = $this->freelanceJobVacancyVector($freelanceJobVacancy);

        return RecommendedFreelanceDevResource::collection($this->recommendDevsByVector(
            $vectorLiteral,
            $data,
            function(Builder $query) use ($freelanceJobVacancy) {
                $this->applyFreelanceRecommendationPreferences($query, $freelanceJobVacancy);

                $query->withExists([
                    'freelanceProposals as has_proposal' => fn(Builder $q) => $q->where('freelance_job_vacancy_id', $freelanceJobVacancy->id),
                    'freelanceInvitations as is_invited' => fn(Builder $q) => $q->where('freelance_job_vacancy_id', $freelanceJobVacancy->id),
                ]);
            }
        ));

    }

    /**
     * Ranking dos orçamentos pendentes de um freela, para o cliente dono. O score junta:
     *
     * - a aderência do dev ao freela: similaridade de cosseno entre o embedding do perfil
     *   e o do freela. Dev sem embedding ainda entra, só que pontuando zero aqui;
     * - a proximidade do preço com o budget do cliente: o menor dividido pelo maior entre
     *   o total do orçamento e o budget. Cobrar o dobro ou a metade do budget pesa igual (0,5).
     *
     * Os pesos de cada parte ficam em config('app.freelance_proposal_ranking').
     */
    public function recommendProposalsForFreelanceJobVacancy(array $data): AnonymousResourceCollection
    {

        $freelanceJobVacancy = $this->findClientFreelanceJobVacancy($data['freelance_job_vacancy_id']);

        $vectorLiteral = $this->freelanceJobVacancyVector($freelanceJobVacancy);

        $limit = $data['limit'] ?? 10;
        $budget = (string) $freelanceJobVacancy->budget;
        $similarityWeight = (float) config('app.freelance_proposal_ranking.similarity_weight');
        $priceWeight = (float) config('app.freelance_proposal_ranking.price_weight');

        $similaritySql = '1 - (dev_profile_embeddings.embedding <=> ?::vector)';
        $priceScoreSql = 'LEAST(freelance_proposals.total_price, ?::numeric) / NULLIF(GREATEST(freelance_proposals.total_price, ?::numeric), 0)';

        $proposals = FreelanceProposal::query()
            ->leftJoin('dev_profile_embeddings', 'dev_profile_embeddings.dev_profile_id', '=', 'freelance_proposals.dev_profile_id')
            ->select('freelance_proposals.*')
            ->selectRaw("{$similaritySql} AS similarity", [$vectorLiteral])
            ->selectRaw("({$priceScoreSql})::float8 AS price_score", [$budget, $budget])
            // O Postgres não aceita alias de coluna dentro de uma expressão, então o score repete as duas partes
            ->selectRaw(
                "?::float8 * COALESCE({$similaritySql}, 0) + ?::float8 * COALESCE(({$priceScoreSql})::float8, 0) AS score",
                [$similarityWeight, $vectorLiteral, $priceWeight, $budget, $budget]
            )
            ->where('freelance_proposals.freelance_job_vacancy_id', $freelanceJobVacancy->id)
            ->where('freelance_proposals.status', FreelanceProposalStatusEnum::PENDING)
            ->with('devProfile.user')
            ->orderByDesc('score')
            ->orderBy('freelance_proposals.created_at')
            ->limit($limit)
            ->get();

        return RecommendedFreelanceProposalResource::collection($proposals);

    }

    /**
     * O inverso da recomendação de devs para um freela: os freelas abertos mais aderentes
     * ao perfil do dev autenticado, comparando o embedding do perfil com o de cada freela.
     * Saem os freelas em que o dev já orçou.
     */
    public function recommendFreelanceJobVacanciesForDev(array $data): AnonymousResourceCollection
    {

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        if(!$authUser->hasRole('dev')) {
            throw new ApiException("You must be a developer to get freelance recommendations!", 403);
        }

        $devProfile = $authUser->dev_profile;

        if(!$devProfile) {
            throw new ApiException("This action needs a existent profile to proceed!", 403);
        }

        $vector = DevProfileEmbedding::query()
            ->where('dev_profile_id', $devProfile->id)
            ->value('embedding');

        if(!$vector) {
            throw new ApiException("Your profile doesn't have an embedding yet!", 409);
        }

        // O binding precisa ir como literal "[1,2,3]": array vira 1536 bindings e desalinha o resto da query
        $vectorLiteral = is_array($vector) ? '[' . implode(',', $vector) . ']' : $vector;

        $limit = $data['limit'] ?? 10;
        $minSimilarity = $data['min_similarity'] ?? null;

        $freelanceJobVacancies = FreelanceJobVacancy::query()
            ->join('freelance_job_vacancy_embeddings', 'freelance_job_vacancy_embeddings.freelance_job_vacancy_id', '=', 'freelance_job_vacancies.id')
            ->selectRaw(
                'freelance_job_vacancies.*, 1 - (freelance_job_vacancy_embeddings.embedding <=> ?::vector) AS similarity',
                [$vectorLiteral]
            )
            ->where('freelance_job_vacancies.status', FreelanceJobVacancyStatusEnum::OPEN)
            ->whereDoesntHave('proposals', fn(Builder $query) => $query->where('dev_profile_id', $devProfile->id))
            ->tap(fn(Builder $query) => $this->applyDevFreelancePreferences($query, $devProfile))
            ->when(!is_null($minSimilarity), function($query) use ($vectorLiteral, $minSimilarity) {
                $query->whereRaw(
                    '1 - (freelance_job_vacancy_embeddings.embedding <=> ?::vector) >= ?',
                    [$vectorLiteral, $minSimilarity]
                );
            })
            ->with(['languages', 'clientProfile.user'])
            ->withExists(['invitations as is_invited' => fn(Builder $query) => $query->where('dev_profile_id', $devProfile->id)])
            // Ordena pela distância crua para o pgvector poder usar índice
            ->orderByRaw('freelance_job_vacancy_embeddings.embedding <=> ?::vector', [$vectorLiteral])
            ->limit($limit)
            ->get();

        return RecommendedFreelanceJobVacancyResource::collection($freelanceJobVacancies);

    }

    /**
     * Carrega o freela garantindo que ele pertence ao cliente autenticado
     */
    private function findClientFreelanceJobVacancy(string $freelanceJobVacancyId): FreelanceJobVacancy
    {

        $freelanceJobVacancy = FreelanceJobVacancy::findOrFail($freelanceJobVacancyId);

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        if(!$authUser->hasRole('client')) {
            throw new ApiException("You must be a client to get recommendations!", 403);
        }

        $clientProfile = $authUser->client_profile;

        if(!$clientProfile || $freelanceJobVacancy->client_profile_id !== $clientProfile->id) {
            throw new ApiException("This freelance job vacancy doesn't belong to you!", 403);
        }

        return $freelanceJobVacancy;

    }

    /**
     * Embedding do freela no literal do pgvector. Ele é gerado em fila, cerca de um
     * minuto depois de o freela ser criado
     */
    private function freelanceJobVacancyVector(FreelanceJobVacancy $freelanceJobVacancy): string
    {

        $vector = FreelanceJobVacancyEmbedding::query()
            ->where('freelance_job_vacancy_id', $freelanceJobVacancy->id)
            ->value('embedding');

        if(!$vector) {
            throw new ApiException("This freelance job vacancy doesn't have an embedding yet!", 409);
        }

        // O binding precisa ir como literal "[1,2,3]": array vira 1536 bindings e desalinha o resto da query
        return is_array($vector) ? '[' . implode(',', $vector) . ']' : $vector;

    }

    /**
     * Busca os devs mais próximos de um vetor de vaga, já no literal do pgvector.
     * `limit` (padrão 10) e `min_similarity` vêm do request; $applyPreferences deixa
     * cada tipo de vaga aplicar os filtros que fazem sentido para ela.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, DevProfile>
     */
    private function recommendDevsByVector(string $vectorLiteral, array $data, Closure $applyPreferences): EloquentCollection
    {

        $limit = $data['limit'] ?? 10;
        $minSimilarity = $data['min_similarity'] ?? null;

        // 1 - distância de cosseno (<=>) = similaridade (1 = idêntico)
        return DevProfile::query()
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
            ->tap($applyPreferences)
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

    }

    /**
     * Vaga freelance não tem tipo de contrato nem modalidade: é sempre contratação como
     * contractor e sem exigência de presença. Por isso só entram aqui os filtros que
     * mapeiam sem inventar regra — min_remuneration fica de fora porque é um salário mensal
     * e o freela tem um orçamento pelo serviço inteiro (o preço entra no ranking de
     * orçamentos), e distância porque a vaga não exige presença.
     */
    private function applyFreelanceRecommendationPreferences(Builder $query, FreelanceJobVacancy $freelanceJobVacancy): void
    {
        $requiredLanguageIds = $freelanceJobVacancy->languages()->pluck('languages.id');

        $query->whereRaw('COALESCE(recommendation_preferences.allow_contractor, true) = true');
        $query->whereRaw('COALESCE(recommendation_preferences.allow_remote, true) = true');

        $this->filterByBlackListedLanguages($query, $requiredLanguageIds);
        $this->filterByStackFlexibility($query, $requiredLanguageIds);
    }

    /**
     * Os mesmos filtros de linguagem da recomendação de devs, olhando do lado do dev: sai o
     * freela que exige alguma linguagem da blacklist dele e, sem flexibilidade de stack, o
     * que não pede nenhuma linguagem entre as hard skills dele (freela sem stack exigida
     * passa). Contrato e modalidade não entram: quem pede freela recomendado já está
     * procurando freela.
     */
    private function applyDevFreelancePreferences(Builder $query, DevProfile $devProfile): void
    {
        $preference = $devProfile->recommendation_preference;

        $blackListedLanguageIds = $preference?->blackListedLanguages()->pluck('languages.id') ?? collect();

        if($blackListedLanguageIds->isNotEmpty()) {
            $query->whereDoesntHave('languages', fn(Builder $q) => $q->whereIn('languages.id', $blackListedLanguageIds));
        }

        // Sem preferência salva, vale o default da migration (flexível)
        if($preference?->allow_stack_flexibility === false) {
            $devLanguageIds = $devProfile->hard_skills()->pluck('language_id');

            $query->where(function(Builder $query) use ($devLanguageIds) {
                $query->whereDoesntHave('languages')
                    ->orWhereHas('languages', fn(Builder $q) => $q->whereIn('languages.id', $devLanguageIds));
            });
        }
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
