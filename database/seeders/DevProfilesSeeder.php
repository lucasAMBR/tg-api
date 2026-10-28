<?php

namespace Database\Seeders;

use App\Models\DevProfile;
use App\Models\Language;
use App\Models\RecommendationPreference;
use App\Models\SoftSkill;
use App\Models\User;
use App\Services\Profiles\ProfileEmbeddingService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

/**
 * Popula 20 devs completos (experiência, projetos, formação, hard e soft skills, cursos,
 * endereço e preferências de recomendação) a partir de database/seeders/data/devs.json.
 *
 * Os perfis foram desenhados para se diferenciarem tanto no conteúdo (stacks e senioridades
 * distintas, o que muda o embedding) quanto nas preferências de recomendação, de forma que
 * uma mesma vaga inclua uns e descarte outros pelos filtros do RecommendationService.
 */
class DevProfilesSeeder extends Seeder
{
    private const PASSWORD = 'user1234!';

    /** @var Collection<string, string> slug da linguagem => id */
    private Collection $languageIds;

    /** @var Collection<string, SoftSkill> nome da soft skill => model com as respostas carregadas */
    private Collection $softSkills;

    public function run(): void
    {
        $devs = $this->loadDevs();

        if (!$devs) {
            return;
        }

        $this->languageIds = Language::pluck('id', 'slug');
        $this->softSkills = SoftSkill::with('responses')->get()->keyBy('name');

        $devProfiles = [];

        foreach ($devs as $index => $data) {
            $devProfiles[] = DB::transaction(fn () => $this->seedDev($data, $index));
        }

        $this->generateEmbeddings($devProfiles);
    }

    private function loadDevs(): ?array
    {
        $path = database_path('seeders/data/devs.json');

        if (!file_exists($path)) {
            $this->command->warn("Arquivo não encontrado: {$path}");
            return null;
        }

        $devs = json_decode(file_get_contents($path), true);

        if (!is_array($devs) || $devs === []) {
            $this->command->warn('devs.json inválido ou vazio.');
            return null;
        }

        return $devs;
    }

    private function seedDev(array $data, int $index): DevProfile
    {
        $user = User::firstOrCreate(
            ['email' => $data['email']],
            [
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ]
        );

        if (!$user->hasRole('dev')) {
            $user->assignRole('dev');
        }

        $devProfile = DevProfile::updateOrCreate(
            ['user_id' => $user->id],
            [...$data['profile'], 'cpf' => $this->fakeCpf($index)]
        );

        // O seeder é re-executável: os filhos são recriados do zero para não duplicar.
        $this->clearRelations($devProfile);

        $this->seedAddress($devProfile, $data['address']);
        $this->seedEmploymentHistories($devProfile, $data['employment_histories'] ?? []);
        $this->seedProjectHistories($devProfile, $data['project_histories'] ?? []);
        $this->seedAcademicBackgrounds($devProfile, $data['academic_backgrounds'] ?? []);
        $this->seedAdditionalCourses($devProfile, $data['additional_courses'] ?? []);
        $this->seedHardSkills($devProfile, $data['hard_skills'] ?? []);
        $this->seedSoftSkills($devProfile, $data['soft_skills'] ?? []);
        $this->seedRecommendationPreference($devProfile, $data['recommendation_preference'] ?? []);

        return $devProfile;
    }

    private function clearRelations(DevProfile $devProfile): void
    {
        $devProfile->employment_histories()->forceDelete();
        $devProfile->project_histories()->forceDelete();
        $devProfile->academic_backgrounds()->forceDelete();
        $devProfile->additional_courses()->forceDelete();
        $devProfile->hard_skills()->forceDelete();
        $devProfile->dev_soft_skills()->forceDelete();
    }

    private function seedAddress(DevProfile $devProfile, array $address): void
    {
        // Criado pela relação morph para que addressable_id e addressable_type sejam preenchidos,
        // já que não estão no fillable do Address.
        $devProfile->address()->forceDelete();
        $devProfile->address()->create($address);
    }

    private function seedEmploymentHistories(DevProfile $devProfile, array $employments): void
    {
        foreach ($employments as $employment) {
            $devProfile->employment_histories()->create($employment);
        }
    }

    private function seedProjectHistories(DevProfile $devProfile, array $projects): void
    {
        foreach ($projects as $project) {
            $projectHistory = $devProfile->project_histories()->create([
                'title' => $project['title'],
                'description' => $project['description'],
                'prod_url' => $project['prod_url'] ?? null,
                'github_url' => $project['github_url'] ?? null,
            ]);

            $projectHistory->languages()->sync($this->resolveLanguageIds($project['languages'] ?? []));
        }
    }

    private function seedAcademicBackgrounds(DevProfile $devProfile, array $backgrounds): void
    {
        foreach ($backgrounds as $background) {
            $devProfile->academic_backgrounds()->create($background);
        }
    }

    private function seedAdditionalCourses(DevProfile $devProfile, array $courses): void
    {
        foreach ($courses as $course) {
            $devProfile->additional_courses()->create($course);
        }
    }

    private function seedHardSkills(DevProfile $devProfile, array $hardSkills): void
    {
        foreach ($hardSkills as $hardSkill) {
            $languageId = $this->languageIds[$hardSkill['language']] ?? null;

            if (!$languageId) {
                $this->command->warn("Linguagem não encontrada: {$hardSkill['language']}");
                continue;
            }

            $devProfile->hard_skills()->create([
                'language_id' => $languageId,
                'skill_level' => $hardSkill['skill_level'],
            ]);
        }
    }

    private function seedSoftSkills(DevProfile $devProfile, array $softSkills): void
    {
        foreach ($softSkills as $softSkill) {
            $skill = $this->softSkills[$softSkill['soft_skill']] ?? null;

            if (!$skill) {
                $this->command->warn("Soft skill não encontrada: {$softSkill['soft_skill']}");
                continue;
            }

            $response = $skill->responses->firstWhere('title', $softSkill['level']);

            if (!$response) {
                $this->command->warn("Nível '{$softSkill['level']}' não existe para {$skill->name}.");
                continue;
            }

            $devProfile->dev_soft_skills()->create([
                'soft_skill_id' => $skill->id,
                'soft_skill_level_response_id' => $response->id,
            ]);
        }
    }

    private function seedRecommendationPreference(DevProfile $devProfile, array $preference): void
    {
        $blackList = $preference['black_listed_languages'] ?? [];
        unset($preference['black_listed_languages']);

        $recommendationPreference = RecommendationPreference::updateOrCreate(
            ['dev_profile_id' => $devProfile->id],
            $preference
        );

        $recommendationPreference->blackListedLanguages()->sync($this->resolveLanguageIds($blackList));
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
     * Usa o mesmo caminho da produção (ProfileEmbeddingService), para o seed exercitar o fluxo real.
     *
     * @param array<int, DevProfile> $devProfiles
     */
    private function generateEmbeddings(array $devProfiles): void
    {
        if (!config('app.services.embedding.key') || !config('app.services.embedding.url')) {
            $this->command->warn('OPEN_IA_KEY/OPEN_IA_URL não configuradas: devs criados sem embedding.');
            return;
        }

        $profileEmbeddingService = app(ProfileEmbeddingService::class);

        foreach ($devProfiles as $devProfile) {
            $devProfile->refresh()->load([
                'employment_histories',
                'academic_backgrounds',
                'hard_skills',
                'dev_soft_skills',
                'additional_courses',
                'project_histories.languages',
            ]);

            // Uma falha pontual da OpenAI (timeout, rate limit) não deve derrubar a rodada inteira:
            // o dev fica sem embedding e o seeder segue para o próximo.
            try {
                $profileEmbeddingService->createDevProfileEmbedding($devProfile);
            } catch (Throwable $exception) {
                $this->command->warn("Erro ao gerar o embedding de {$devProfile->name}: {$exception->getMessage()}");
            }
        }
    }

    /**
     * CPF sintético com dígitos verificadores válidos, para não repetir o mesmo número em todos os devs.
     */
    private function fakeCpf(int $seed): string
    {
        $digits = str_split(str_pad((string) (100000000 + ($seed * 7919) % 800000000), 9, '0', STR_PAD_LEFT));

        for ($position = 9; $position < 11; $position++) {
            $sum = 0;

            foreach (array_slice($digits, 0, $position) as $offset => $digit) {
                $sum += ((int) $digit) * (($position + 1) - $offset);
            }

            $digits[$position] = (string) (((10 * $sum) % 11) % 10);
        }

        return implode('', $digits);
    }
}
