<?php

namespace App\Services\Dashboard;

use App\Enums\DevJobVacancyInterviewStatusEnum;
use App\Enums\DevJobVacancyLiveCodingStatusEnum;
use App\Enums\DevJobVacancyStatusEnum;
use App\Enums\DevSpecialtyEnum;
use App\Enums\FreelanceJobVacancyStatusEnum;
use App\Enums\FreelanceProposalStatusEnum;
use App\Enums\JobVacancyStatusEnum;
use App\Enums\OperationalSegmentEnum;
use App\Enums\SelectionProcessStageEnum;
use App\Enums\SeniorityLevelEnum;
use App\Exceptions\ApiException;
use App\Helpers\ProfileHelper;
use App\Models\AdminProfile;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ClientProfile;
use App\Models\Comment;
use App\Models\CompanyProfile;
use App\Models\DevJobVacancyInterview;
use App\Models\DevJobVacancyLiveCoding;
use App\Models\DevProfile;
use App\Models\FreelanceJobVacancy;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use BackedEnum;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Números das dashboards já no formato dos gráficos do front: as distribuições por enum trazem
 * todos os casos, com zero quando não há registro, e as séries mensais trazem todos os meses do
 * período, para as barras e linhas não pularem categorias.
 */
class DashboardService
{
    public const DEFAULT_TIMELINE_MONTHS = 6;

    /**
     * Quantas vagas e linguagens entram nos rankings
     */
    private const RANKING_LIMIT = 10;

    /**
     * Janela dos números "nos últimos dias"
     */
    private const RECENT_DAYS = 30;

    public function company(array $data): array
    {
        $companyProfile = $this->authCompanyProfile();

        $vacancies = DB::table('job_vacancies')
            ->where('job_vacancies.company_profile_id', $companyProfile->id)
            ->whereNull('job_vacancies.deleted_at');

        $applications = DB::table('dev_job_vacancy')
            ->join('job_vacancies', 'job_vacancies.id', '=', 'dev_job_vacancy.job_vacancy_id')
            ->where('job_vacancies.company_profile_id', $companyProfile->id)
            ->whereNull('job_vacancies.deleted_at');

        // O perfil do dev entra mesmo excluído, para a soma das distribuições bater com o total
        $applicants = $applications->clone()
            ->join('dev_profiles', 'dev_profiles.id', '=', 'dev_job_vacancy.dev_profile_id');

        $vacanciesByStatus = $this->countBy($vacancies, 'job_vacancies.status');
        $applicationsByStatus = $this->countBy($applications, 'dev_job_vacancy.status');

        $vacanciesTotal = $vacanciesByStatus->sum();
        $applicationsTotal = $applicationsByStatus->sum();
        $hiredTotal = $applicationsByStatus[DevJobVacancyStatusEnum::APPROVED->value] ?? 0;

        // Vagas em processo seletivo são as que já encerraram as inscrições
        $vacanciesByStage = $this->totalsByStage($this->countBy(
            $vacancies->clone()->where('job_vacancies.status', JobVacancyStatusEnum::CLOSED_INSCRIPTIONS->value),
            'job_vacancies.process_step'
        ));

        // A recusa mantém a etapa em que aconteceu, então as recusadas mostram onde o funil afunila
        $inProgressByStage = $this->totalsByStage($this->countBy(
            $applications->clone()->where('dev_job_vacancy.status', DevJobVacancyStatusEnum::IN_PROGRESS->value),
            'dev_job_vacancy.process_step'
        ));
        $rejectedByStage = $this->totalsByStage($this->countBy(
            $applications->clone()->where('dev_job_vacancy.status', DevJobVacancyStatusEnum::REJECTED->value),
            'dev_job_vacancy.process_step'
        ));

        $stages = $this->stagesInUse($vacancies, $vacanciesByStage, $inProgressByStage, $rejectedByStage);

        return [
            'summary' => [
                'vacancies_total' => $vacanciesTotal,
                'vacancies_active' => ($vacanciesByStatus[JobVacancyStatusEnum::OPEN_INSCRIPTIONS->value] ?? 0)
                    + ($vacanciesByStatus[JobVacancyStatusEnum::CLOSED_INSCRIPTIONS->value] ?? 0),
                'applications_total' => $applicationsTotal,
                'applications_in_progress' => $applicationsByStatus[DevJobVacancyStatusEnum::IN_PROGRESS->value] ?? 0,
                'hired_total' => $hiredTotal,
                'hire_rate' => $this->percentage($hiredTotal, $applicationsTotal),
                'average_applications_per_vacancy' => $vacanciesTotal > 0
                    ? round($applicationsTotal / $vacanciesTotal, 1)
                    : 0,
                'applications_last_30_days' => $applications->clone()
                    ->where('dev_job_vacancy.created_at', '>=', now()->subDays(self::RECENT_DAYS))
                    ->count(),
            ],
            'agenda' => $this->companyAgenda($companyProfile),
            'vacancies_by_status' => $this->enumBreakdown(JobVacancyStatusEnum::class, $vacanciesByStatus),
            'vacancies_by_process_step' => array_map(fn (SelectionProcessStageEnum $stage) => [
                ...$this->enumItem($stage),
                'count' => $vacanciesByStage[$stage->value]['total'] ?? 0,
                'awaiting_count' => $vacanciesByStage[$stage->value]['awaiting'] ?? 0,
            ], $stages),
            'applications_by_status' => $this->enumBreakdown(DevJobVacancyStatusEnum::class, $applicationsByStatus),
            'applications_by_process_step' => array_map(fn (SelectionProcessStageEnum $stage) => [
                ...$this->enumItem($stage),
                'in_progress' => $inProgressByStage[$stage->value]['total'] ?? 0,
                'rejected' => $rejectedByStage[$stage->value]['total'] ?? 0,
            ], $stages),
            'applications_by_vacancy' => $this->applicationsByVacancy($vacancies),
            'applicants_by_specialty' => $this->enumBreakdown(
                DevSpecialtyEnum::class,
                $this->countBy($applicants, 'dev_profiles.specialty')
            ),
            'applicants_by_seniority' => $this->enumBreakdown(
                SeniorityLevelEnum::class,
                $this->countBy($applicants, 'dev_profiles.seniority_level')
            ),
            'timeline' => $this->timeline($data['months'] ?? self::DEFAULT_TIMELINE_MONTHS, [
                'vacancies' => [$vacancies, 'job_vacancies.created_at'],
                'applications' => [$applications, 'dev_job_vacancy.created_at'],
            ]),
        ];
    }

    public function admin(array $data): array
    {
        $vacancies = DB::table('job_vacancies')->whereNull('job_vacancies.deleted_at');

        $applications = DB::table('dev_job_vacancy')
            ->join('job_vacancies', 'job_vacancies.id', '=', 'dev_job_vacancy.job_vacancy_id')
            ->whereNull('job_vacancies.deleted_at');

        $proposals = DB::table('freelance_proposals')
            ->join('freelance_job_vacancies', 'freelance_job_vacancies.id', '=', 'freelance_proposals.freelance_job_vacancy_id')
            ->whereNull('freelance_job_vacancies.deleted_at');

        // Só o que ainda aparece na rede: sem posts excluídos nem comentários removidos
        $posts = Post::query();
        $comments = Comment::query()->whereNull('removed_at')->whereHas('post');
        $likes = Like::query()->whereHasMorph('likeable', [Post::class, Comment::class], function (Builder $query, string $type) {
            if ($type === Comment::class) {
                $query->whereNull('removed_at')->whereHas('post');
            }
        });
        $favorites = DB::table('favorite_posts')
            ->join('posts', 'posts.id', '=', 'favorite_posts.post_id')
            ->whereNull('posts.deleted_at');

        $devsBySpecialty = $this->countBy(DevProfile::query(), 'specialty');
        $vacanciesBySpecialty = $this->countBy($vacancies, 'job_vacancies.specialties');
        $applicationsBySpecialty = $this->countBy($applications, 'job_vacancies.specialties');
        $applicationsByStatus = $this->countBy($applications, 'dev_job_vacancy.status');

        $social = [
            'posts' => $posts->count(),
            'comments' => $comments->count(),
            'likes' => $likes->count(),
            'favorites' => $favorites->count(),
            'follows' => DB::table('follows')->count(),
            'chat_conversations' => ChatConversation::query()->whereNotNull('last_message_at')->count(),
            'chat_messages' => ChatMessage::query()->count(),
        ];

        // Mesma composição da popularidade dos usuários: curtidas, comentários, favoritos e seguidores
        $social['interactions'] = $social['likes'] + $social['comments'] + $social['favorites'] + $social['follows'];

        return [
            'summary' => [
                'users_total' => User::query()->count(),
                'new_users_last_30_days' => User::query()->where('created_at', '>=', now()->subDays(self::RECENT_DAYS))->count(),
                'blocked_users' => User::query()->where('is_blocked', true)->count(),
                'devs' => $devsBySpecialty->sum(),
                'companies' => CompanyProfile::query()->count(),
                'clients' => ClientProfile::query()->count(),
                'admins' => AdminProfile::query()->count(),
                'devs_open_to_work' => DevProfile::query()->where('open_to_work', true)->count(),
                'devs_seniority_tested' => DevProfile::query()->where('seniority_tested', true)->count(),
                'job_vacancies_total' => $vacancies->count(),
                'job_vacancies_open' => $vacancies->clone()->where('job_vacancies.status', JobVacancyStatusEnum::OPEN_INSCRIPTIONS->value)->count(),
                'applications_total' => $applicationsByStatus->sum(),
                'hired_total' => $applicationsByStatus[DevJobVacancyStatusEnum::APPROVED->value] ?? 0,
                'freelance_vacancies_total' => FreelanceJobVacancy::query()->count(),
                'freelance_proposals_total' => $proposals->count(),
                'freelance_contracted_volume' => round((float) $proposals->clone()
                    ->where('freelance_proposals.status', FreelanceProposalStatusEnum::ACCEPTED->value)
                    ->sum('freelance_proposals.total_price'), 2),
            ],
            'specialties' => array_map(fn (DevSpecialtyEnum $specialty) => [
                ...$this->enumItem($specialty),
                'devs' => $devsBySpecialty[$specialty->value] ?? 0,
                'job_vacancies' => $vacanciesBySpecialty[$specialty->value] ?? 0,
                'applications' => $applicationsBySpecialty[$specialty->value] ?? 0,
            ], DevSpecialtyEnum::cases()),
            'devs_by_seniority' => $this->enumBreakdown(
                SeniorityLevelEnum::class,
                $this->countBy(DevProfile::query(), 'seniority_level')
            ),
            'companies_by_segment' => $this->enumBreakdown(
                OperationalSegmentEnum::class,
                $this->countBy(CompanyProfile::query(), 'operational_segment')
            ),
            'job_vacancies_by_status' => $this->enumBreakdown(
                JobVacancyStatusEnum::class,
                $this->countBy($vacancies, 'job_vacancies.status')
            ),
            'applications_by_status' => $this->enumBreakdown(DevJobVacancyStatusEnum::class, $applicationsByStatus),
            'freelance_vacancies_by_status' => $this->enumBreakdown(
                FreelanceJobVacancyStatusEnum::class,
                $this->countBy(FreelanceJobVacancy::query(), 'status')
            ),
            'freelance_proposals_by_status' => $this->enumBreakdown(
                FreelanceProposalStatusEnum::class,
                $this->countBy($proposals, 'freelance_proposals.status')
            ),
            'top_dev_languages' => $this->topLanguages(
                DB::table('hard_skills')
                    ->join('dev_profiles', 'dev_profiles.id', '=', 'hard_skills.dev_profile_id')
                    ->whereNull('hard_skills.deleted_at')
                    ->whereNull('dev_profiles.deleted_at'),
                'hard_skills.language_id',
                'hard_skills.dev_profile_id'
            ),
            'top_vacancy_languages' => $this->topLanguages(
                DB::table('job_vacancy_languages')
                    ->join('job_vacancies', 'job_vacancies.id', '=', 'job_vacancy_languages.job_vacancy_id')
                    ->whereNull('job_vacancies.deleted_at'),
                'job_vacancy_languages.languages_id',
                'job_vacancy_languages.job_vacancy_id'
            ),
            'social' => $social,
            'timeline' => $this->timeline($data['months'] ?? self::DEFAULT_TIMELINE_MONTHS, [
                'users' => [User::query(), 'created_at'],
                'applications' => [$applications, 'dev_job_vacancy.created_at'],
                'posts' => [$posts, 'created_at'],
                'comments' => [$comments, 'created_at'],
                'likes' => [$likes, 'created_at'],
            ]),
        ];
    }

    /**
     * O admin também vê a dashboard da empresa, desde que esteja usando o perfil de empresa
     */
    private function authCompanyProfile(): CompanyProfile
    {
        $profile = ProfileHelper::getUserProfileByRole(Auth::user());

        if (!$profile instanceof CompanyProfile) {
            throw new ApiException('This dashboard is only available for company profiles!', 403);
        }

        return $profile;
    }

    /**
     * Calls que a empresa tem marcadas e as que dependem dela confirmar o horário proposto pelo dev
     *
     * @return array{upcoming_interviews: int, upcoming_live_codings: int, interviews_awaiting_confirmation: int, live_codings_awaiting_confirmation: int}
     */
    private function companyAgenda(CompanyProfile $companyProfile): array
    {
        $ofCompany = fn (Builder $query) => $query->where('company_profile_id', $companyProfile->id);

        $interviews = DevJobVacancyInterview::query()->whereHas('jobVacancy', $ofCompany);
        $liveCodings = DevJobVacancyLiveCoding::query()->whereHas('jobVacancy', $ofCompany);

        return [
            'upcoming_interviews' => $interviews->clone()
                ->where('status', DevJobVacancyInterviewStatusEnum::APPROVED->value)
                ->where('scheduled_at', '>=', now())
                ->count(),
            'upcoming_live_codings' => $liveCodings->clone()
                ->where('status', DevJobVacancyLiveCodingStatusEnum::APPROVED->value)
                ->where('scheduled_at', '>=', now())
                ->count(),
            'interviews_awaiting_confirmation' => $interviews->clone()
                ->where('status', DevJobVacancyInterviewStatusEnum::AWAITING_COMPANY_CONFIRMATION->value)
                ->count(),
            'live_codings_awaiting_confirmation' => $liveCodings->clone()
                ->where('status', DevJobVacancyLiveCodingStatusEnum::AWAITING_COMPANY_CONFIRMATION->value)
                ->count(),
        ];
    }

    /**
     * As vagas com mais candidaturas, com o andamento delas. Vagas sem candidatura também entram,
     * depois das outras, para a empresa ver quais não atraíram ninguém
     */
    private function applicationsByVacancy(QueryBuilder $vacancies): array
    {
        $countStatus = 'sum(case when dev_job_vacancy.status = ? then 1 else 0 end)';

        return $vacancies->clone()
            ->leftJoin('dev_job_vacancy', 'dev_job_vacancy.job_vacancy_id', '=', 'job_vacancies.id')
            ->groupBy('job_vacancies.id', 'job_vacancies.title', 'job_vacancies.title_pt', 'job_vacancies.title_en', 'job_vacancies.status', 'job_vacancies.created_at')
            ->select('job_vacancies.id', 'job_vacancies.title', 'job_vacancies.title_pt', 'job_vacancies.title_en', 'job_vacancies.status')
            ->selectRaw('count(dev_job_vacancy.id) as applications_count')
            ->selectRaw("{$countStatus} as in_progress", [DevJobVacancyStatusEnum::IN_PROGRESS->value])
            ->selectRaw("{$countStatus} as approved", [DevJobVacancyStatusEnum::APPROVED->value])
            ->selectRaw("{$countStatus} as rejected", [DevJobVacancyStatusEnum::REJECTED->value])
            ->orderByDesc('applications_count')
            ->orderByDesc('job_vacancies.created_at')
            ->limit(self::RANKING_LIMIT)
            ->get()
            ->map(fn (object $vacancy) => [
                'id' => $vacancy->id,
                'title' => $vacancy->title,
                'title_pt' => $vacancy->title_pt,
                'title_en' => $vacancy->title_en,
                'status' => $vacancy->status,
                'status_label' => JobVacancyStatusEnum::labelFromValue($vacancy->status),
                'applications_count' => (int) $vacancy->applications_count,
                'in_progress' => (int) $vacancy->in_progress,
                'approved' => (int) $vacancy->approved,
                'rejected' => (int) $vacancy->rejected,
            ])
            ->all();
    }

    /**
     * Junta cada etapa `awaiting_*` à etapa que ela espera começar, guardando à parte quantos
     * estão na espera: é a parte que depende da empresa preparar o que a etapa exige
     *
     * @param Collection<string, int> $totals total por valor de process_step
     * @return array<string, array{total: int, awaiting: int}> por valor da etapa já iniciada
     */
    private function totalsByStage(Collection $totals): array
    {
        $stages = [];

        foreach ($totals as $step => $total) {
            $stage = SelectionProcessStageEnum::tryFrom($step);

            if (!$stage) {
                continue;
            }

            $startedStage = $stage->startedStage()->value;

            $stages[$startedStage]['total'] = ($stages[$startedStage]['total'] ?? 0) + $total;
            $stages[$startedStage]['awaiting'] = ($stages[$startedStage]['awaiting'] ?? 0) + ($stage->isAwaiting() ? $total : 0);
        }

        return $stages;
    }

    /**
     * Etapas que aparecem nos processos seletivos das vagas ou que têm algum número, na ordem
     * do enum, para o gráfico não mostrar etapas que a empresa nunca usa
     *
     * @param array<string, mixed> ...$totalsByStage
     * @return list<SelectionProcessStageEnum>
     */
    private function stagesInUse(QueryBuilder $vacancies, array ...$totalsByStage): array
    {
        $configuredSteps = DB::table('job_vacancy_process_steps')
            ->whereIn('job_vacancy_id', $vacancies->clone()->select('job_vacancies.id'))
            ->distinct()
            ->pluck('step')
            ->all();

        $stepsWithTotals = array_merge(...array_map('array_keys', $totalsByStage));

        return array_values(array_filter(
            SelectionProcessStageEnum::cases(),
            fn (SelectionProcessStageEnum $stage) => !$stage->isAwaiting()
                && (in_array($stage->value, $configuredSteps, true) || in_array($stage->value, $stepsWithTotals, true))
        ));
    }

    /**
     * Linguagens mais frequentes, contando cada dev ou vaga uma vez só
     *
     * @return list<array{id: string, name: string, count: int}>
     */
    private function topLanguages(QueryBuilder $query, string $languageColumn, string $ownerColumn): array
    {
        return $query
            ->join('languages', 'languages.id', '=', $languageColumn)
            ->whereNull('languages.deleted_at')
            ->groupBy('languages.id', 'languages.name')
            ->select('languages.id', 'languages.name')
            ->selectRaw("count(distinct {$ownerColumn}) as total")
            ->orderByDesc('total')
            ->orderBy('languages.name')
            ->limit(self::RANKING_LIMIT)
            ->get()
            ->map(fn (object $language) => [
                'id' => $language->id,
                'name' => $language->name,
                'count' => (int) $language->total,
            ])
            ->all();
    }

    /**
     * @return Collection<string, int> total por valor da coluna
     */
    private function countBy(Builder|QueryBuilder $query, string $column): Collection
    {
        return $query->clone()
            ->groupBy($column)
            ->selectRaw("{$column} as group_value, count(*) as total")
            ->pluck('total', 'group_value')
            ->map(fn ($total) => (int) $total);
    }

    /**
     * Um item por caso do enum, na ordem em que ele declara. Valores gravados que não são
     * casos do enum ficam de fora
     *
     * @param class-string<BackedEnum> $enum
     * @param Collection<string, int> $totals
     * @return list<array{value: string, label: string, i18nKey: string, count: int}>
     */
    private function enumBreakdown(string $enum, Collection $totals): array
    {
        return array_map(fn (BackedEnum $case) => [
            ...$this->enumItem($case),
            'count' => $totals[$case->value] ?? 0,
        ], $enum::cases());
    }

    /**
     * Mesmo formato do EnumResource
     *
     * @return array{value: string, label: string, i18nKey: string}
     */
    private function enumItem(BackedEnum $case): array
    {
        return [
            'value' => $case->value,
            'label' => $case->label(),
            'i18nKey' => $case->i18nKey(),
        ];
    }

    /**
     * Total de cada série por mês de criação, dos últimos $months meses com o atual incluso.
     * Meses sem registro vêm com zero, para a linha do gráfico não pular meses
     *
     * @param array<string, array{0: Builder|QueryBuilder, 1: string}> $series nome da série => [consulta, coluna da data]
     * @return list<array<string, string|int>>
     */
    private function timeline(int $months, array $series): array
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths($months - 1);

        $totals = array_map(
            fn (array $entry) => $this->monthlyCounts($entry[0], $entry[1], $start),
            $series
        );

        return array_map(function (int $offset) use ($start, $totals) {
            $month = $start->addMonths($offset)->format('Y-m');

            return ['month' => $month] + array_map(fn (array $byMonth) => $byMonth[$month] ?? 0, $totals);
        }, range(0, $months - 1));
    }

    /**
     * @return array<string, int> total por mês no formato YYYY-MM
     */
    private function monthlyCounts(Builder|QueryBuilder $query, string $column, CarbonImmutable $start): array
    {
        $month = $this->monthExpression($column);

        return $query->clone()
            ->where($column, '>=', $start)
            ->selectRaw("{$month} as month, count(*) as total")
            ->groupByRaw($month)
            ->pluck('total', 'month')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    /**
     * Os testes rodam no SQLite e a aplicação no Postgres, que formatam datas com funções diferentes
     */
    private function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            default => "date_format({$column}, '%Y-%m')",
        };
    }

    private function percentage(int $part, int $total): float
    {
        return $total > 0 ? round($part / $total * 100, 1) : 0.0;
    }
}
