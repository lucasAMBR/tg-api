<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use App\Models\JobVacancy;
use App\Models\Language;
use App\Models\SoftSkill;
use App\Models\User;
use App\Services\JobVacancy\JobVacancyEmbeddingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Popula as vagas das empresas criadas pelo CompanyProfilesSeeder
 * a partir de database/seeders/data/job_vacancies.json.
 *
 * As vagas variam em senioridade, modalidade, tipo de contrato, faixa salarial e stack,
 * para que a listagem, a busca e as recomendações tenham cenários suficientes para testar
 * inclusão e descarte de devs pelos filtros de preferência.
 */
class JobVacancySeeder extends Seeder
{
    /** @var Collection<string, string> slug da linguagem => id */
    private Collection $languageIds;

    /** @var Collection<string, string> nome da soft skill => id */
    private Collection $softSkillIds;

    /** @var Collection<string, string> email do usuário da empresa => id do company profile */
    private Collection $companyProfileIds;

    public function run(): void
    {
        $vacancies = $this->loadVacancies();

        if (!$vacancies) {
            return;
        }

        $this->languageIds = Language::pluck('id', 'slug');
        $this->softSkillIds = SoftSkill::pluck('id', 'name');
        $this->companyProfileIds = $this->loadCompanyProfileIds();

        if ($this->companyProfileIds->isEmpty()) {
            $this->command->warn('Nenhum perfil de empresa encontrado: rode o CompanyProfilesSeeder antes.');
            return;
        }

        $jobVacancies = [];

        foreach ($vacancies as $data) {
            $jobVacancy = DB::transaction(fn () => $this->seedJobVacancy($data));

            if ($jobVacancy) {
                $jobVacancies[] = $jobVacancy;
            }
        }

        $this->generateEmbeddings($jobVacancies);
    }

    private function loadVacancies(): ?array
    {
        $path = database_path('seeders/data/job_vacancies.json');

        if (!file_exists($path)) {
            $this->command->warn("Arquivo não encontrado: {$path}");
            return null;
        }

        $vacancies = json_decode(file_get_contents($path), true);

        if (!is_array($vacancies) || $vacancies === []) {
            $this->command->warn('job_vacancies.json inválido ou vazio.');
            return null;
        }

        return $vacancies;
    }

    /**
     * @return Collection<string, string>
     */
    private function loadCompanyProfileIds(): Collection
    {
        return CompanyProfile::query()
            ->join('users', 'users.id', '=', 'company_profiles.user_id')
            ->pluck('company_profiles.id', 'users.email');
    }

    private function seedJobVacancy(array $data): ?JobVacancy
    {
        $companyProfileId = $this->companyProfileIds[$data['company_email']] ?? null;

        if (!$companyProfileId) {
            $this->command->warn("Empresa não encontrada: {$data['company_email']}");
            return null;
        }

        // O seeder é re-executável: a mesma vaga da mesma empresa é atualizada em vez de duplicada.
        // withTrashed() para que uma vaga excluída em testes volte em vez de virar uma cópia nova.
        $jobVacancy = JobVacancy::withTrashed()->updateOrCreate(
            [
                'company_profile_id' => $companyProfileId,
                'title' => $data['title'],
            ],
            [
                'description' => $data['description'],
                'employment_type' => $data['employment_type'],
                'benefits' => $data['benefits'],
                'estimated_salary' => $data['estimated_salary'],
                'contract_type' => $data['contract_type'],
                'seniority_level' => $data['seniority_level'],
                'specialties' => $data['specialties'],
            ]
        );

        if ($jobVacancy->trashed()) {
            $jobVacancy->restore();
        }

        $this->seedLanguages($jobVacancy, $data['languages'] ?? []);

        $jobVacancy->desirableLanguage()->sync($this->resolveLanguageIds($data['languages_desirable'] ?? []));
        $jobVacancy->softSkill()->sync($this->resolveSoftSkillIds($data['soft_skills'] ?? []));

        $this->seedProcessSteps($jobVacancy, $data['process_steps'] ?? []);

        return $jobVacancy;
    }

    /**
     * @param array<int, array{slug: string, level: string}> $languages
     */
    private function seedLanguages(JobVacancy $jobVacancy, array $languages): void
    {
        $pivot = [];

        foreach ($languages as $language) {
            $languageId = $this->languageIds[$language['slug']] ?? null;

            if (!$languageId) {
                $this->command->warn("Linguagem não encontrada: {$language['slug']}");
                continue;
            }

            $pivot[$languageId] = ['language_level' => $language['level']];
        }

        $jobVacancy->languages()->sync($pivot);
    }

    /**
     * @param array<int, string> $steps
     */
    private function seedProcessSteps(JobVacancy $jobVacancy, array $steps): void
    {
        $jobVacancy->processSteps()->delete();

        // A ordem da etapa vem da posição no array, então o JSON descreve o processo na sequência real.
        $jobVacancy->processSteps()->createMany(
            collect($steps)->values()->map(fn (string $step, int $index) => [
                'step' => $step,
                'order' => $index + 1,
            ])->all()
        );
    }

    /**
     * @param array<int, string> $slugs
     * @return array<int, string>
     */
    private function resolveLanguageIds(array $slugs): array
    {
        return collect($slugs)
            ->map(fn (string $slug) => $this->languageIds[$slug] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param array<int, string> $names
     * @return array<int, string>
     */
    private function resolveSoftSkillIds(array $names): array
    {
        return collect($names)
            ->map(fn (string $name) => $this->softSkillIds[$name] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param array<int, JobVacancy> $jobVacancies
     */
    private function generateEmbeddings(array $jobVacancies): void
    {
        if (!config('app.services.embedding.key') || !config('app.services.embedding.url')) {
            $this->command->warn('OPEN_IA_KEY/OPEN_IA_URL não configuradas: vagas criadas sem embedding.');
            return;
        }

        $jobVacancyEmbeddingService = app(JobVacancyEmbeddingService::class);

        foreach ($jobVacancies as $jobVacancy) {
            // Uma falha pontual da OpenAI (timeout, rate limit) não deve derrubar a rodada inteira:
            // a vaga fica sem embedding e o seeder segue para a próxima.
            try {
                $jobVacancyEmbeddingService->createJobVacancyEmbedding($jobVacancy->refresh());
            } catch (Throwable $exception) {
                $this->command->warn("Erro ao gerar o embedding da vaga {$jobVacancy->title}: {$exception->getMessage()}");
            }
        }
    }
}
