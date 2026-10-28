<?php

namespace App\Services\ProficiencyTest;

use App\Enums\ProficiencyTestResponseStatusEnum;
use App\Enums\ProficiencyTestStatusEnum;
use App\Enums\SeniorityLevelEnum;
use App\Enums\TranslationStatusEnum;
use App\Exceptions\ApiException;
use App\Http\Resources\ProficiencyTest\ProficiencyTestCollection;
use App\Http\Resources\ProficiencyTest\ProficiencyTestResource;
use App\Http\Resources\ProficiencyTest\ProficiencyTestReviewResource;
use App\Http\Resources\Question\QuestionResource;
use App\Jobs\CalculateProficiencyTestScore;
use App\Jobs\GenerateProficiencyTest;
use App\Models\DevProfile;
use App\Models\ProficiencyTest;
use App\Models\ProficiencyTestResponse;
use App\Models\ProficiencyTestVisualization;
use App\Models\Question;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProficiencyTestService
{

    private $frontendBaseCategories = [
        'programming_logic',
        'clean_code',
        'frontend_fundamentals'
    ];

    private $backendBaseCategories = [
        'programming_logic',
        'clean_code',
        'security_backend',
        'backend_data_handling',
        'devops'
    ];

    private $fullstackBaseCategories = [
        'programming_logic',
        'clean_code',
        'security_backend',
        'backend_data_handling',
        'devops',
        'frontend_fundamentals'
    ];

    public function index(array $data)
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $devProfileId = $data['dev_profile_id'] ?? null;
        $seniorityLevel = $data['seniority_level'] ?? null;
        $specialty = $data['specialty'] ?? null;
        $status = $data['status'] ?? null;

        $proficiencyTests = ProficiencyTest::query()
            ->with('devProfile')
            ->when($search, function (Builder $query, $search) {
                $query->whereHas('devProfile', function (Builder $query) use ($search) {
                    $query->where('name', 'ILIKE', "%{$search}%");
                });
            })
            ->when($devProfileId, function (Builder $query, $devProfileId) {
                $query->where('dev_profile_id', $devProfileId);
            })
            ->when($seniorityLevel, function (Builder $query, $seniorityLevel) {
                $query->where('seniority_level', $seniorityLevel);
            })
            ->when($specialty, function (Builder $query, $specialty) {
                $query->where('specialty', $specialty);
            })
            ->when($status, function (Builder $query, $status) {
                $query->where('status', $status);
            })
            ->latest('solicitation_date')
            ->paginate($perPage, ['*'], 'page', $page);

        return new ProficiencyTestCollection($proficiencyTests);
    }

    public function show(array $data): ProficiencyTestResource
    {
        $proficiencyTest = ProficiencyTest::findOrFail($data['id']);

        return new ProficiencyTestResource($proficiencyTest);
    }

    public function registerVisualization(array $data): ProficiencyTestVisualization
    {
        $proficiencyTest = ProficiencyTest::findOrFail($data['id']);

        return ProficiencyTestVisualization::create([
            'proficiency_test_id' => $proficiencyTest->id,
            'type' => $data['type'],
        ]);
    }

    public function solicitateProficiencyTest(array $data)
    {
        $devProfile = DevProfile::findOrFail($data['dev_profile_id']);

        $lastTest = ProficiencyTest::where('dev_profile_id', $devProfile->id)
            ->latest('solicitation_date')
            ->first();

        if ($lastTest) {
            $hasUnansweredTest = $lastTest->proficiencyTestResponses()
                ->where('status', ProficiencyTestResponseStatusEnum::AWAITING_RESPONSE->value)
                ->exists();

            if ($hasUnansweredTest) {
                throw new ApiException('You must finish your last proficiency test before requesting a new one!', 422);
            }

            if ($lastTest->solicitation_date->greaterThan(now()->subMonth())) {
                throw new ApiException('You can only request a new proficiency test once a month!', 422);
            }
        }

        $proficiencyTest = ProficiencyTest::create([
            'dev_profile_id' => $devProfile->id,
            'seniority_level' => $devProfile->seniority_level,
            'specialty' => $devProfile->specialty,
            'backend_category' => $data['backend_category'] ?? null,
            'frontend_category' => $data['frontend_category'] ?? null,
            'status' => ProficiencyTestStatusEnum::PENDING->value,
            'solicitation_date' => now(),
        ]);

        GenerateProficiencyTest::dispatch($devProfile, $proficiencyTest, $data);

        return new ProficiencyTestResource($proficiencyTest);
    }

    /**
     * As questões do teste, agrupadas em páginas.
     *
     * @return Collection<int, Collection<int, QuestionResource>>
     */
    public function getProficiencyTestQuestions(array $data): Collection
    {
        $proficiencyTest = ProficiencyTest::findOrFail($data['id']);

        $proficiencyTest->load('proficiencyTestResponses.question.question_responses');

        return $proficiencyTest->proficiencyTestResponses
            ->pluck('question')
            ->filter()
            ->values()
            ->chunk(config('app.proficiency_test.questions_per_page'))
            ->map(fn ($chunk) => $chunk->values()->map(fn ($question) => new QuestionResource($question)))
            ->values();
    }

    public function getProficiencyTestReview(array $data)
    {
        $proficiencyTest = ProficiencyTest::findOrFail($data['id']);

        $proficiencyTest->load([
            'proficiencyTestResponses.question.question_responses',
            'proficiencyTestResponses.questionResponse',
        ]);

        return ProficiencyTestReviewResource::collection($proficiencyTest->proficiencyTestResponses);
    }

    public function submitProficiencyTest(array $data)
    {
        $proficiencyTest = ProficiencyTest::findOrFail($data['id']);

        $chunks = $data['chunks'];

        if ($proficiencyTest->status === ProficiencyTestStatusEnum::COMPLETED->value) {
            throw new ApiException('This proficiency test has already been submitted!', 422);
        }

        return DB::transaction(function () use ($proficiencyTest, $chunks) {
            foreach ($chunks as $chunk) {
                $questionIds = collect($chunk['responses'])->pluck('question_id');

                $idealTimes = Question::whereIn('id', $questionIds)
                    ->pluck('ideal_time_to_solve', 'id');

                $totalIdealTime = $idealTimes->sum();

                foreach ($chunk['responses'] as $response) {
                    $idealTime = $idealTimes[$response['question_id']] ?? 0;

                    $proportion = $totalIdealTime > 0 ? $idealTime / $totalIdealTime : 0;

                    $timeTaken = (int) round($chunk['time_taken'] * $proportion);

                    ProficiencyTestResponse::where('proficiency_test_id', $proficiencyTest->id)
                        ->where('question_id', $response['question_id'])
                        ->update([
                            'question_response_id' => $response['response_id'],
                            'time_taken' => $timeTaken,
                            'alt_tabs_used' => $chunk['alt_tabs'],
                            'status' => ProficiencyTestResponseStatusEnum::ANSWERED->value,
                        ]);
                }
            }

            $proficiencyTest->update([
                'status' => ProficiencyTestStatusEnum::AWAITING_SCORE->value,
            ]);

            $proficiencyTest->devProfile->notifications()->create([
                'type' => 'proficiency_test_analysis_start',
                'title' => 'Teste de proficiência',
                'message' => 'Estamos iniciando a análise do seu teste de proficiência. Em alguns minutos você poderá ver o resultado!',
            ]);

            CalculateProficiencyTestScore::dispatch($proficiencyTest);

            return new ProficiencyTestResource($proficiencyTest->fresh());
        });
    }

    public function calculateProficiencyTestScore(ProficiencyTest $proficiencyTest): int
    {
        $proficiencyTest->load('proficiencyTestResponses.question', 'proficiencyTestResponses.questionResponse');

        $responses = $proficiencyTest->proficiencyTestResponses;

        $correctResponses = $responses->filter(fn ($response) => $response->questionResponse?->is_correct);

        $baseScore = $correctResponses->sum(fn ($response) => $response->question?->difficulty_level ?? 0);

        $totalHitRate = $responses->count() > 0 ? $correctResponses->count() / $responses->count() : 0;

        $mastery = $this->handleLevelMasteryMap($proficiencyTest->seniority_level, $responses);

        $boost = $this->handleConsistencyBoost($proficiencyTest->seniority_level, $mastery);
        $scoreAfterBoost = $baseScore * $boost;

        $penalty = $this->handlePagePenalties($responses);
        $finalScore = $scoreAfterBoost * max(0, 1 - $penalty);

        $score = (int) round($finalScore);

        $evaluatedSeniorityLevel = $this->handleSeniorityAdjustment($proficiencyTest->seniority_level, $mastery, $totalHitRate);

        $profileScoreAwarded = $this->handleProfileScoreGrade($score, (int) $proficiencyTest->max_score);
        $previousProfileScoreAwarded = $this->getPreviousProfileScoreAwarded($proficiencyTest);

        Log::channel('proficiency_test')->info('Proficiency test score calculated', [
            'proficiency_test_id' => $proficiencyTest->id,
            'dev_profile_id' => $proficiencyTest->dev_profile_id,
            'seniority_level' => $proficiencyTest->seniority_level,
            'total_questions' => $responses->count(),
            'correct_answers' => $correctResponses->count(),
            'hit_rate' => round($totalHitRate, 4),
            'base_score' => $baseScore,
            'boost_multiplier' => $boost,
            'score_after_boost' => round($scoreAfterBoost, 2),
            'page_penalty' => round($penalty, 4),
            'max_score' => $proficiencyTest->max_score,
            'final_score' => $score,
            'evaluated_seniority_level' => $evaluatedSeniorityLevel,
            'profile_score_awarded' => $profileScoreAwarded,
            'previous_profile_score_awarded' => $previousProfileScoreAwarded,
        ]);

        $proficiencyTest->update([
            'status' => ProficiencyTestStatusEnum::COMPLETED->value,
            'score' => $score,
            'profile_score_awarded' => $profileScoreAwarded,
        ]);

        $devProfile = $proficiencyTest->devProfile;

        $devProfile->update([
            'seniority_level' => $evaluatedSeniorityLevel,
            'seniority_tested' => true,
            'score' => max(0, ($devProfile->score ?? 0) - $previousProfileScoreAwarded + $profileScoreAwarded),
        ]);

        $this->notifyScoreResult($proficiencyTest, $proficiencyTest->seniority_level, $evaluatedSeniorityLevel);

        return $score;
    }

    private function handleProfileScoreGrade(int $score, int $maxScore): int
    {
        if ($maxScore <= 0) {
            return 1;
        }

        return max(1, min(10, (int) round(($score / $maxScore) * 10)));
    }

    private function getPreviousProfileScoreAwarded(ProficiencyTest $proficiencyTest): int
    {
        return (int) ProficiencyTest::where('dev_profile_id', $proficiencyTest->dev_profile_id)
            ->where('id', '!=', $proficiencyTest->id)
            ->where('status', ProficiencyTestStatusEnum::COMPLETED->value)
            ->whereNotNull('profile_score_awarded')
            ->latest('solicitation_date')
            ->value('profile_score_awarded');
    }

    private function notifyScoreResult(ProficiencyTest $proficiencyTest, string $declaredLevel, string $evaluatedLevel): void
    {
        $declared = SeniorityLevelEnum::from($declaredLevel);
        $evaluated = SeniorityLevelEnum::from($evaluatedLevel);

        if ($evaluated->hierarchyLevel() > $declared->hierarchyLevel()) {
            $message = "Calculamos a pontuação do seu teste de proficiência e sua senioridade subiu para {$evaluated->labelPt()}. Parabéns!";
        } elseif ($evaluated->hierarchyLevel() < $declared->hierarchyLevel()) {
            $message = "Calculamos a pontuação do seu teste de proficiência e sua senioridade foi ajustada para {$evaluated->labelPt()}.";
        } else {
            $message = "Calculamos a pontuação do seu teste de proficiência e aferimos com sucesso a sua senioridade de {$evaluated->labelPt()}!";
        }

        $proficiencyTest->devProfile->notifications()->create([
            'type' => 'proficiency_test_score_calculated',
            'title' => 'Teste de proficiência',
            'message' => $message,
        ]);
    }

    private function handleLevelMasteryMap(string $declaredLevel, Collection $responses): array
    {
        $thresholds = config('app.proficiency_test.mastery_thresholds');

        $testLevels = array_keys($this->handleDevSeniorityLevel($declaredLevel));
        $declaredIndex = array_search($declaredLevel, $testLevels);

        $belowLevel = $testLevels[$declaredIndex - 1] ?? null;
        $aboveLevel = $testLevels[$declaredIndex + 1] ?? null;

        return [
            'below_level' => $belowLevel,
            'above_level' => $aboveLevel,
            'mastered_below' => $belowLevel ? $this->handleLevelMastery($responses, $belowLevel, $thresholds['below_level']) : null,
            'mastered_declared' => $this->handleLevelMastery($responses, $declaredLevel, $thresholds['declared_level']),
            'mastered_above' => $aboveLevel ? $this->handleLevelMastery($responses, $aboveLevel, $thresholds['above_level']) : null,
        ];
    }

    private function handleConsistencyBoost(string $declaredLevel, array $mastery): float
    {
        $boosts = config('app.proficiency_test.consistency_boosts');

        $masteredBelow = $mastery['mastered_below'];
        $masteredDeclared = $mastery['mastered_declared'];
        $masteredAbove = $mastery['mastered_above'];

        $isConsistent = ($masteredBelow ?? true) && $masteredDeclared && ! ($masteredAbove ?? false);
        $isInconsistent = ! ($masteredBelow ?? false) && $masteredDeclared && ($masteredAbove ?? true);

        $boost = 1.0;

        if ($isConsistent) {
            $boost = $boosts['positive'];
        } elseif ($isInconsistent) {
            $boost = $boosts['negative'];
        }

        Log::channel('proficiency_test')->info('Consistency boost evaluated', [
            'declared_level' => $declaredLevel,
            'below_level' => $mastery['below_level'],
            'above_level' => $mastery['above_level'],
            'mastered_below' => $masteredBelow,
            'mastered_declared' => $masteredDeclared,
            'mastered_above' => $masteredAbove,
            'is_consistent' => $isConsistent,
            'is_inconsistent' => $isInconsistent,
            'boost' => $boost,
        ]);

        return $boost;
    }

    private function handleSeniorityAdjustment(string $declaredLevel, array $mastery, float $totalHitRate): string
    {
        $adjustment = config('app.proficiency_test.seniority_adjustment');

        $belowLevel = $mastery['below_level'];
        $aboveLevel = $mastery['above_level'];

        $shouldPromote = $aboveLevel !== null
            && $totalHitRate >= $adjustment['promote_min_hit_rate']
            && $mastery['mastered_above'];

        $shouldDemote = $belowLevel !== null
            && ($totalHitRate <= $adjustment['demote_max_hit_rate']
                || (! $mastery['mastered_below'] && ! $mastery['mastered_declared']));

        $evaluatedLevel = $declaredLevel;

        if ($shouldPromote) {
            $evaluatedLevel = $aboveLevel;
        } elseif ($shouldDemote) {
            $evaluatedLevel = $belowLevel;
        }

        Log::channel('proficiency_test')->info('Seniority adjustment evaluated', [
            'declared_level' => $declaredLevel,
            'below_level' => $belowLevel,
            'above_level' => $aboveLevel,
            'total_hit_rate' => round($totalHitRate, 4),
            'mastered_below' => $mastery['mastered_below'],
            'mastered_declared' => $mastery['mastered_declared'],
            'mastered_above' => $mastery['mastered_above'],
            'should_promote' => $shouldPromote,
            'should_demote' => $shouldDemote,
            'evaluated_seniority_level' => $evaluatedLevel,
        ]);

        return $evaluatedLevel;
    }

    private function handleLevelMastery(Collection $responses, string $seniorityLevel, float $requiredHitRate): bool
    {
        $levelResponses = $responses->filter(
            fn ($response) => $response->question?->seniority_level === $seniorityLevel
        );

        if ($levelResponses->isEmpty()) {
            return false;
        }

        $totalCount = $levelResponses->count();

        $correctCount = $levelResponses
            ->filter(fn ($response) => $response->questionResponse?->is_correct)
            ->count();

        $hitRate = $correctCount / $totalCount;
        $mastered = $hitRate >= $requiredHitRate;

        Log::channel('proficiency_test')->info('Level mastery evaluated', [
            'seniority_level' => $seniorityLevel,
            'correct' => $correctCount,
            'total' => $totalCount,
            'hit_rate' => round($hitRate, 4),
            'required_hit_rate' => $requiredHitRate,
            'mastered' => $mastered,
        ]);

        return $mastered;
    }

    private function handlePagePenalties(Collection $responses): float
    {
        $altTabsConfig = config('app.proficiency_test.alt_tabs');
        $timeConfig = config('app.proficiency_test.time');

        $penalty = 0;

        $pages = $responses->chunk(config('app.proficiency_test.questions_per_page'));

        foreach ($pages as $index => $page) {
            $pagePenalty = 0;

            $timeTaken = $page->sum('time_taken');
            $idealTime = $page->sum(fn ($response) => $response->question?->ideal_time_to_solve ?? 0);
            $timeRatio = $idealTime > 0 ? $timeTaken / $idealTime : null;

            if ($timeRatio !== null
                && ($timeRatio > $timeConfig['max_ideal_time_ratio'] || $timeRatio < $timeConfig['min_ideal_time_ratio'])) {
                $pagePenalty += $timeConfig['violation_penalty'];
            }

            $excessAltTabs = max(0, (int) $page->max('alt_tabs_used') - $altTabsConfig['max_per_page']);
            $pagePenalty += $excessAltTabs * $altTabsConfig['penalty_per_excess'];

            Log::channel('proficiency_test')->info('Page penalty evaluated', [
                'page' => $index + 1,
                'time_taken' => $timeTaken,
                'ideal_time' => $idealTime,
                'time_ratio' => $timeRatio !== null ? round($timeRatio, 4) : null,
                'max_alt_tabs' => (int) $page->max('alt_tabs_used'),
                'excess_alt_tabs' => $excessAltTabs,
                'page_penalty' => round($pagePenalty, 4),
            ]);

            $penalty += $pagePenalty;
        }

        return min($penalty, 1);
    }

    public function generateProficiencyTest(DevProfile $devProfile, ProficiencyTest $proficiencyTest, array $data)
    {
        $frontendCategory = $data['frontend_category'] ?? null;
        $backendCategory = $data['backend_category'] ?? null;

        if ($frontendCategory && $backendCategory) {
            return $this->generateFullstackTest($devProfile, $proficiencyTest, $frontendCategory, $backendCategory);
        }

        if ($frontendCategory) {
            return $this->generateFrontendTest($devProfile, $proficiencyTest, $frontendCategory);
        }

        if ($backendCategory) {
            return $this->generateBackendTest($devProfile, $proficiencyTest, $backendCategory);
        }
    }

    private function generateBackendTest(DevProfile $devProfile, ProficiencyTest $proficiencyTest, string $backendCategory)
    {
        $seniorityLevel = $devProfile->seniority_level;

        $categories = array_merge($this->backendBaseCategories, [$backendCategory]);

        $questionComposition = $this->handleDevSeniorityLevel($seniorityLevel);

        $question_list = [];

        foreach ($categories as $category) {
            $questionsPerCategory = $this->composeCategoryQuestions($category, $questionComposition);

            $question_list = array_merge($question_list, $questionsPerCategory);
        }

        foreach ($question_list as $question) {
            ProficiencyTestResponse::create([
                'proficiency_test_id' => $proficiencyTest->id,
                'question_id' => $question->id
            ]);
        }

        $maxScore = collect($question_list)->sum('difficulty_level');

        $proficiencyTest->update([
            'status' => ProficiencyTestStatusEnum::GENERATED->value,
            'max_score' => $maxScore,
        ]);
    }

    private function generateFrontendTest(DevProfile $devProfile, ProficiencyTest $proficiencyTest, string $frontendCategory)
    {
        $seniorityLevel = $devProfile->seniority_level;

        $categories = array_merge($this->frontendBaseCategories, [$frontendCategory]);

        $questionComposition = $this->handleDevSeniorityLevel($seniorityLevel);

        $question_list = [];

        foreach ($categories as $category) {
            $questionsPerCategory = $this->composeCategoryQuestions($category, $questionComposition);

            $question_list = array_merge($question_list, $questionsPerCategory);
        }

        foreach ($question_list as $question) {
            ProficiencyTestResponse::create([
                'proficiency_test_id' => $proficiencyTest->id,
                'question_id' => $question->id
            ]);
        }

        $maxScore = collect($question_list)->sum('difficulty_level');

        $proficiencyTest->update([
            'status' => ProficiencyTestStatusEnum::GENERATED->value,
            'max_score' => $maxScore,
        ]);
    }

    private function generateFullstackTest(DevProfile $devProfile, ProficiencyTest $proficiencyTest, string $frontendCategory, string $backendCategory)
    {
        $seniorityLevel = $devProfile->seniority_level;

        $categories = array_merge($this->fullstackBaseCategories, [$frontendCategory, $backendCategory]);

        $questionComposition = $this->handleDevSeniorityLevel($seniorityLevel);

        $question_list = [];
        
        foreach ($categories as $category) {
            $questionsPerCategory = $this->composeCategoryQuestions($category, $questionComposition);
            
            $question_list = array_merge($question_list, $questionsPerCategory);
        }
        
        foreach ($question_list as $question) {
            ProficiencyTestResponse::create([
                'proficiency_test_id' => $proficiencyTest->id,
                'question_id' => $question->id
            ]);
        }

        $maxScore = collect($question_list)->sum('difficulty_level');

        $proficiencyTest->update([
            'status' => ProficiencyTestStatusEnum::GENERATED->value,
            'max_score' => $maxScore,
        ]);
    }

    private function handleDevSeniorityLevel(string $seniorityLevel)
    {
        return match ($seniorityLevel) {
            SeniorityLevelEnum::INTERN->value => $this->handleInternQuestionsDifficultyPerCategory(),
            SeniorityLevelEnum::JUNIOR->value => $this->handleJuniorQuestionsDifficultyPerCategory(),
            SeniorityLevelEnum::MID_LEVEL->value => $this->handleMidQuestionsDifficultyPerCategory(),
            SeniorityLevelEnum::SENIOR->value => $this->handleSeniorQuestionsDifficultyPerCategory(),
            SeniorityLevelEnum::STAFF->value => $this->handleStaffQuestionsDifficultyPerCategory(),
        };
    }

    private function handleInternQuestionsDifficultyPerCategory()
    {
        return [
            'intern' => 5,
            'junior' => 3,
        ];
    }

    private function handleJuniorQuestionsDifficultyPerCategory()
    {
        return [
            'intern' => 2,
            'junior' => 4,
            'mid_level' => 2,
        ];
    }

    private function handleMidQuestionsDifficultyPerCategory()
    {
        return [
            'junior' => 2,
            'mid_level' => 4,
            'senior' => 2,
        ];
    }

    private function handleSeniorQuestionsDifficultyPerCategory()
    {
        return [
            'mid_level' => 2,
            'senior' => 4,
            'staff' => 2
        ];
    }

    private function handleStaffQuestionsDifficultyPerCategory()
    {
        return [
            'senior' => 3,
            'staff' => 5
        ];
    }

    private function composeCategoryQuestions(string $category, array $questionComposition) {
        $question_list = [];

        foreach ($questionComposition as $level => $quantity) {
            $questions = Question::where('category', $category)
                ->where('seniority_level', $level)
                ->whereHas('question_responses', function ($query) {
                    $query->where('translation_status', TranslationStatusEnum::TRANSLATED->value);
                }, '=', 4)
                ->inRandomOrder()
                ->limit($quantity)
                ->get();

            $question_list = array_merge($question_list, $questions->all());
        }

        return $question_list;
    }
}