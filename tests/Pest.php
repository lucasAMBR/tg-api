<?php

use App\Models\SoftSkill;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\SoftSkillLevelResponseSeeder;
use Database\Seeders\SoftSkillSeeder;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;

pest()->extend(Tests\TestCase::class)
    ->in('Feature');

function seedRolesAndPermissions(): void
{
    (new PermissionSeeder)->run();
    (new RoleSeeder)->run();
}

function createUserWithRole(string $role): User
{
    $user = User::factory()->create();

    $user->assignRole(Role::findByName($role, 'api'));

    return $user;
}

function actingAsApi(User $user): array
{
    $token = JWTAuth::fromUser($user);

    return ['Authorization' => "Bearer {$token}"];
}

function validDevProfilePayload(array $overrides = []): array
{
    return array_merge([
        'cpf' => '39053344705',
        'name' => 'João Silva',
        'bio' => 'Desenvolvedor backend com experiência em Laravel.',
        'phone' => '+5531987654321',
        'birthdate' => '1990-05-20',
        'seniority_level' => 'junior',
        'open_to_relocation' => false,
        'open_to_work' => true,
    ], $overrides);
}

function createDevUserWithProfile(): array
{
    $user = createUserWithRole('dev');

    $devProfile = \App\Models\DevProfile::factory()->create([
        'user_id' => $user->id,
        'cpf' => fake()->unique()->numerify('###########'),
        'phone' => '+5531'.fake()->unique()->numerify('9########'),
    ]);

    return [$user, $devProfile];
}

function validEmploymentHistoryPayload(array $overrides = []): array
{
    return array_merge([
        'company_name' => 'ACME Tecnologia',
        'company_location' => 'São Paulo, SP',
        'position_name' => 'Desenvolvedor Backend',
        'employment_type' => 'remote',
        'contract_type' => 'clt',
        'seniority_level' => 'junior',
        'actuation_details' => 'Desenvolvimento de APIs REST com Laravel.',
        'is_current' => false,
        'start_date' => '2020-01-01',
        'end_date' => '2022-12-31',
    ], $overrides);
}

function seedLanguagesCatalog(): void
{
    (new LanguageSeeder)->run();
}

function seedSoftSkillsCatalog(): void
{
    (new SoftSkillSeeder)->run();
    (new SoftSkillLevelResponseSeeder)->run();
}

function createDevUserWithRecommendationPreference(): array
{
    [$user, $devProfile] = createDevUserWithProfile();

    \App\Models\RecommendationPreference::create([
        'dev_profile_id' => $devProfile->id,
    ]);

    return [$user, $devProfile];
}

function validHardSkillPayload(?string $languageId = null, array $overrides = []): array
{
    $languageId ??= \App\Models\Language::query()->value('id');

    return array_merge([
        'language_id' => $languageId,
        'skill_level' => 'intermediate',
    ], $overrides);
}

function validRecommendationPreferencePayload(array $overrides = []): array
{
    return array_merge([
        'allow_clt' => true,
        'allow_contractor' => true,
        'allow_internship' => false,
        'allow_on_site' => true,
        'allow_hybrid' => true,
        'allow_remote' => true,
        'on_site_job_radius' => 20,
        'hybrid_jobs_radius' => 40,
        'allow_stack_flexibility' => true,
        'min_remuneration' => 5000,
    ], $overrides);
}

function buildDevSoftSkillsPayload(int $evaluationWeightPerSkill): array
{
    $softSkills = SoftSkill::with('responses')->orderBy('name')->get();

    return [
        'soft_skills' => $softSkills->map(function (SoftSkill $skill) use ($evaluationWeightPerSkill) {
            $levelResponse = $skill->responses
                ->firstWhere('evaluation_weight', $evaluationWeightPerSkill);

            return [
                'soft_skill_id' => $skill->id,
                'soft_skill_level_response_id' => $levelResponse->id,
            ];
        })->values()->all(),
    ];
}
