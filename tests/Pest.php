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
    // O guard e o jwt-auth guardam o usuário/token da requisição anterior dentro
    // do mesmo teste; sem isso, trocar de usuário entre requisições não tem efeito.
    auth()->forgetGuards();
    app('tymon.jwt')->unsetToken();

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
        'specialty' => 'backend',
        'open_to_relocation' => false,
        'open_to_work' => true,
    ], $overrides);
}

function createDevUserWithProfile(array $profileOverrides = []): array
{
    $user = createUserWithRole('dev');

    $devProfile = \App\Models\DevProfile::factory()->create(array_merge([
        'user_id' => $user->id,
        'cpf' => '52998224725',
        'phone' => '+5531999887766',
    ], $profileOverrides));

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

function seedSoftSkills(): void
{
    (new \Database\Seeders\SoftSkillSeeder)->run();
    (new \Database\Seeders\SoftSkillLevelResponseSeeder)->run();
}

function validDevSoftSkillPayload(int $evaluationWeight = 1): array
{
    $softSkills = \App\Models\SoftSkill::with('responses')->get();

    return [
        'soft_skills' => $softSkills->map(function ($skill) use ($evaluationWeight) {
            $response = $skill->responses->firstWhere('evaluation_weight', $evaluationWeight);

            return [
                'soft_skill_id' => $skill->id,
                'soft_skill_level_response_id' => $response->id,
            ];
        })->values()->all(),
    ];
}

function createClientUserWithProfile(array $profileOverrides = []): array
{
    $user = createUserWithRole('client');

    $clientProfile = \App\Models\ClientProfile::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Maria Cliente',
        'bio' => 'Contrato freelas para os projetos da minha loja.',
        'phone' => '+5531' . fake()->unique()->numerify('9########'),
        'cpf' => '39053344705',
        'birthdate' => '1988-03-10',
    ], $profileOverrides));

    return [$user, $clientProfile];
}

/**
 * Dev com telefone único, para criar vários no mesmo teste
 */
function createFreelanceDevUser(array $profileOverrides = []): array
{
    $user = createUserWithRole('dev');

    $devProfile = \App\Models\DevProfile::factory()->create(array_merge([
        'user_id' => $user->id,
    ], $profileOverrides));

    return [$user, $devProfile];
}

/**
 * Freela aberto de um tipo que não exige stack, para dispensar as linguagens
 */
function createFreelanceJobVacancy(\App\Models\ClientProfile $clientProfile, array $overrides = []): \App\Models\FreelanceJobVacancy
{
    return \App\Models\FreelanceJobVacancy::create(array_merge([
        'title' => 'Landing page em Next.js',
        'description' => 'Landing page institucional com formulário de contato.',
        'job_type' => 'design',
        'budget' => 10000,
        'seniority_level' => 'mid_level',
        'specialties' => ['frontend'],
        'client_profile_id' => $clientProfile->id,
    ], $overrides));
}

function createFreelanceProposal(\App\Models\FreelanceJobVacancy $freelanceJobVacancy, \App\Models\DevProfile $devProfile, array $overrides = []): \App\Models\FreelanceProposal
{
    return \App\Models\FreelanceProposal::create(array_merge([
        'freelance_job_vacancy_id' => $freelanceJobVacancy->id,
        'dev_profile_id' => $devProfile->id,
        'pricing_type' => 'fixed',
        'total_price' => 9000,
    ], $overrides));
}

/**
 * Freela com o contrato já fechado com o dev informado
 */
function createContractedFreelance(\App\Models\ClientProfile $clientProfile, \App\Models\DevProfile $devProfile, array $overrides = []): \App\Models\FreelanceJobVacancy
{
    $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile, array_merge([
        'status' => 'in_progress',
        'contracted_at' => now(),
    ], $overrides));

    createFreelanceProposal($freelanceJobVacancy, $devProfile, ['status' => 'accepted']);

    return $freelanceJobVacancy;
}
