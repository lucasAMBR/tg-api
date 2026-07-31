<?php

namespace App\Services\Recommendation;

use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Models\DevProfile;
use App\Models\JobVacancy;
use App\Models\JobVacancyEmbedding;
use Illuminate\Support\Facades\Auth;

class RecommendationService {

    public function recommendDevsForJobVacancy(JobVacancy $jobVacancy, array $data) {

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

        $limit = $data['limit'] ?? 10;
        $minSimilarity = $data['min_similarity'] ?? null;

        // 1 - distância de cosseno (<=>) = similaridade (1 = idêntico)
        return DevProfile::query()
            ->join('dev_profile_embeddings', 'dev_profile_embeddings.dev_profile_id', '=', 'dev_profiles.id')
            ->selectRaw(
                'dev_profiles.*, 1 - (dev_profile_embeddings.embedding <=> ?::vector) AS similarity',
                [$vector]
            )
            ->when(!is_null($minSimilarity), function($query) use ($vector, $minSimilarity) {
                $query->whereRaw(
                    '1 - (dev_profile_embeddings.embedding <=> ?::vector) >= ?',
                    [$vector, $minSimilarity]
                );
            })
            // Ordena pela distância crua para o pgvector poder usar índice
            ->orderByRaw('dev_profile_embeddings.embedding <=> ?::vector', [$vector])
            ->limit($limit)
            ->get();

    }

}
