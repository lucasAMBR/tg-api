<?php

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
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
        'cpf' => '52998224725',
        'phone' => '+5531999887766',
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
