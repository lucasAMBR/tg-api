<?php

namespace App\Services\ProfileScore;

use App\Enums\DevJobVacancyStatusEnum;
use App\Enums\FreelanceJobVacancyStatusEnum;
use App\Enums\FreelanceProposalStatusEnum;
use App\Enums\ProficiencyTestStatusEnum;
use App\Enums\SelectionProcessStageEnum;
use App\Models\ClientProfile;
use App\Models\Comment;
use App\Models\CompanyProfile;
use App\Models\DevJobVacancy;
use App\Models\DevProfile;
use App\Models\FreelanceJobVacancy;
use App\Models\FreelanceProposal;
use App\Models\JobVacancy;
use App\Models\Like;
use App\Models\Post;
use App\Models\ProficiencyTest;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Score de credibilidade dos perfis, de 0 a config('app.profile_score.max').
 *
 * Cada tipo de perfil tem as suas fontes de pontos (config('app.profile_score.goals')) e o
 * máximo é dividido em partes iguais entre elas: com 4 fontes, cada uma vale até 25 pontos.
 * Assim ninguém chega ao topo explorando uma fonte só.
 *
 * O score é sempre recalculado a partir do que está no banco, nunca somado aos poucos:
 * desfazer uma curtida devolve os pontos dela, e calcular de novo dá o mesmo resultado.
 */
class ProfileScoreService
{
    /**
     * Recalcula e grava o score do perfil
     */
    public function recalculate(DevProfile|CompanyProfile|ClientProfile $profile): int
    {
        $score = (int) round(array_sum($this->breakdown($profile)));

        if ((int) $profile->score !== $score) {
            // Direto na tabela para o score, que muda por ação de outras pessoas (um favorito,
            // o avanço de uma etapa), não mexer no updated_at do perfil
            $profile->newQuery()->whereKey($profile->getKey())->toBase()->update(['score' => $score]);

            $profile->setAttribute('score', $score)->syncOriginalAttribute('score');
        }

        return $score;
    }

    /**
     * Pontos de cada fonte do perfil, já limitados ao teto da fonte
     *
     * @return array<string, float>
     */
    public function breakdown(DevProfile|CompanyProfile|ClientProfile $profile): array
    {
        $goals = config('app.profile_score.goals.' . $this->profileType($profile));
        $sourceMax = config('app.profile_score.max') / count($goals);

        return collect($goals)
            ->map(fn (int|float $goal, string $source) => $sourceMax * min(1, $this->occurrences($profile, $source) / $goal))
            ->all();
    }

    private function occurrences(DevProfile|CompanyProfile|ClientProfile $profile, string $source): float
    {
        return match ($source) {
            'social' => $this->socialInteractions($profile->user_id),
            'selection_process_steps' => $this->selectionProcessStepsPassed($profile),
            'selection_processes_completed' => $this->approvedApplications($profile),
            'proficiency_tests' => $this->proficiencyTests($profile),
            'job_vacancies_created' => JobVacancy::query()->where('company_profile_id', $profile->id)->count(),
            'selection_process_steps_concluded' => $this->selectionProcessStepsConcluded($profile),
            'freelance_job_vacancies_posted' => FreelanceJobVacancy::query()->where('client_profile_id', $profile->id)->count(),
            'freelance_proposals_accepted' => $this->acceptedFreelanceProposals($profile),
            'freelance_job_vacancies_completed' => FreelanceJobVacancy::query()
                ->where('client_profile_id', $profile->id)
                ->where('status', FreelanceJobVacancyStatusEnum::COMPLETED)
                ->count(),
            default => throw new InvalidArgumentException("Unknown profile score source: {$source}"),
        };
    }

    /**
     * Curtidas e comentários no conteúdo dos outros, mais os favoritos que as postagens do
     * usuário receberam, cada um com o seu peso. Interagir com o próprio conteúdo não conta.
     *
     * A curtida e o comentário continuam valendo se a postagem for excluída depois, porque a
     * interação aconteceu. Já o favorito sai junto com a postagem favoritada
     */
    private function socialInteractions(string $userId): float
    {
        $weights = config('app.profile_score.social_weights');

        $likes = Like::query()
            ->where('user_id', $userId)
            ->whereHasMorph('likeable', [Post::class, Comment::class], function (Builder $query, string $type) use ($userId) {
                if ($type === Post::class) {
                    $query->withTrashed();
                }

                $query->where('user_id', '!=', $userId);
            })
            ->count();

        $comments = Comment::query()
            ->where('user_id', $userId)
            ->whereNull('removed_at')
            ->whereHas('post', fn (Builder $query) => $query->withTrashed()->where('user_id', '!=', $userId))
            ->count();

        $favorites = DB::table('favorite_posts')
            ->join('posts', 'posts.id', '=', 'favorite_posts.post_id')
            ->where('posts.user_id', $userId)
            ->whereNull('posts.deleted_at')
            ->where('favorite_posts.user_id', '!=', $userId)
            ->count();

        return $likes * $weights['like'] + $comments * $weights['comment'] + $favorites * $weights['favorite'];
    }

    /**
     * Etapas em que o dev foi aprovado, somando todas as candidaturas. A candidatura não guarda
     * o histórico, mas guarda a etapa em que está: estar na 3ª etapa do processo da vaga é ter
     * passado pelas 2 anteriores, e terminar aprovado é ter passado pela última também.
     * Só se candidatar não conta, para não valer a pena se inscrever em tudo
     */
    private function selectionProcessStepsPassed(DevProfile $profile): int
    {
        $stepsBefore = DB::table('dev_job_vacancy')
            ->join('job_vacancy_process_steps', function (JoinClause $join) {
                $join->on('job_vacancy_process_steps.job_vacancy_id', '=', 'dev_job_vacancy.job_vacancy_id')
                    ->on('job_vacancy_process_steps.step', '=', $this->startedStepExpression('dev_job_vacancy.process_step'));
            })
            ->where('dev_job_vacancy.dev_profile_id', $profile->id)
            ->sum($this->stepsBeforeExpression());

        return (int) $stepsBefore + $this->approvedApplications($profile);
    }

    /**
     * Candidaturas que chegaram ao fim do processo seletivo aprovadas
     */
    private function approvedApplications(DevProfile $profile): int
    {
        return DevJobVacancy::query()
            ->where('dev_profile_id', $profile->id)
            ->where('status', DevJobVacancyStatusEnum::APPROVED)
            ->count();
    }

    /**
     * Testes concluídos pesados pela nota: um teste com nota máxima vale uma ocorrência
     * inteira, um com nota 5 vale meia. O teste só pode ser pedido uma vez por mês, então
     * repetir não enche a fonte de uma vez
     */
    private function proficiencyTests(DevProfile $profile): float
    {
        $grades = ProficiencyTest::query()
            ->where('dev_profile_id', $profile->id)
            ->where('status', ProficiencyTestStatusEnum::COMPLETED->value)
            ->sum('profile_score_awarded');

        return $grades / ProficiencyTest::MAX_PROFILE_SCORE_AWARDED;
    }

    /**
     * Etapas de processo seletivo que a empresa concluiu, somando todas as vagas. Como na
     * candidatura, a vaga só guarda a etapa em que está: estar na 3ª etapa é ter concluído as
     * 2 anteriores. A última conta depois do avanço final, quando ninguém mais está em
     * andamento nela
     */
    private function selectionProcessStepsConcluded(CompanyProfile $profile): int
    {
        $stepsBefore = DB::table('job_vacancies')
            ->join('job_vacancy_process_steps', function (JoinClause $join) {
                $join->on('job_vacancy_process_steps.job_vacancy_id', '=', 'job_vacancies.id')
                    ->on('job_vacancy_process_steps.step', '=', $this->startedStepExpression('job_vacancies.process_step'));
            })
            ->where('job_vacancies.company_profile_id', $profile->id)
            ->whereNull('job_vacancies.deleted_at')
            ->sum($this->stepsBeforeExpression());

        $lastStepsConcluded = JobVacancy::query()
            ->where('company_profile_id', $profile->id)
            ->where('process_step', function (QueryBuilder $query) {
                $query->select('step')
                    ->from('job_vacancy_process_steps')
                    ->whereColumn('job_vacancy_process_steps.job_vacancy_id', 'job_vacancies.id')
                    ->orderByDesc('order')
                    ->limit(1);
            })
            ->whereHas('applications', fn (Builder $query) => $query->whereColumn('dev_job_vacancy.process_step', 'job_vacancies.process_step'))
            ->whereDoesntHave('applications', fn (Builder $query) => $query
                ->whereColumn('dev_job_vacancy.process_step', 'job_vacancies.process_step')
                ->where('status', DevJobVacancyStatusEnum::IN_PROGRESS))
            ->count();

        return (int) $stepsBefore + $lastStepsConcluded;
    }

    private function acceptedFreelanceProposals(ClientProfile $profile): int
    {
        return FreelanceProposal::query()
            ->where('status', FreelanceProposalStatusEnum::ACCEPTED)
            ->whereHas('freelanceJobVacancy', fn (Builder $query) => $query->where('client_profile_id', $profile->id))
            ->count();
    }

    /**
     * A etapa em si a partir da coluna de etapa, sem a espera (`awaiting_*`) na frente.
     * É o SelectionProcessStageEnum::startedStage() feito no banco
     */
    private function startedStepExpression(string $column): Expression
    {
        $prefix = SelectionProcessStageEnum::AWAITING_PREFIX;

        return DB::raw('REPLACE(' . DB::getQueryGrammar()->wrap($column) . ", '{$prefix}', '')");
    }

    /**
     * Quantas etapas vêm antes da etapa do join no processo seletivo da vaga
     */
    private function stepsBeforeExpression(): Expression
    {
        return DB::raw(DB::getQueryGrammar()->wrap('job_vacancy_process_steps.order') . ' - 1');
    }

    private function profileType(DevProfile|CompanyProfile|ClientProfile $profile): string
    {
        return match (true) {
            $profile instanceof DevProfile => 'dev',
            $profile instanceof CompanyProfile => 'company',
            $profile instanceof ClientProfile => 'client',
        };
    }
}
