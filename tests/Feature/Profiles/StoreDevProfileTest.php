<?php

use App\Models\DevProfile;
use App\Models\User;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

beforeEach(function () {
    seedRolesAndPermissions();
});

describe('POST /api/profile/dev', function () {
    it('allows a user to create a dev profile successfully', function () {
        $user = createUserWithRole('dev');

        $payload = validDevProfilePayload();

        $response = postJson('/api/profile/dev', $payload, actingAsApi($user));

        $response
            ->assertCreated()
            ->assertJson([
                'error' => false,
                'message' => 'Profile created with Success',
            ])
            ->assertJsonPath('data.name', $payload['name'])
            ->assertJsonPath('data.cpf', $payload['cpf'])
            ->assertJsonPath('data.seniority_level', $payload['seniority_level']);

        assertDatabaseHas('dev_profiles', [
            'user_id' => $user->id,
            'cpf' => $payload['cpf'],
            'name' => $payload['name'],
        ]);
    });

    it('prevents a user who already has a dev profile from creating another', function () {
        $user = createUserWithRole('dev');

        DevProfile::factory()->create([
            'user_id' => $user->id,
            'cpf' => '52998224725',
            'phone' => '+5531999887766',
        ]);

        $response = postJson(
            '/api/profile/dev',
            validDevProfilePayload([
                'cpf' => '39053344705',
                'phone' => '+5531987654321',
            ]),
            actingAsApi($user)
        );

        $response
            ->assertUnprocessable()
            ->assertJson([
                'error' => true,
            ])
            ->assertJsonPath('data.dev_profile.0', 'You already have a developer profile.');

        expect(DevProfile::where('user_id', $user->id)->count())->toBe(1);
    });

    it('denies profile creation when the user lacks permission', function () {
        $user = createUserWithRole('client');

        $response = postJson(
            '/api/profile/dev',
            validDevProfilePayload(),
            actingAsApi($user)
        );

        $response
            ->assertForbidden()
            ->assertJson([
                'error' => true,
                'message' => 'Acesso negado.',
            ]);

        assertDatabaseHas('users', ['id' => $user->id]);
        expect(DevProfile::where('user_id', $user->id)->exists())->toBeFalse();
    });

    it('rejects creation when cpf format is invalid', function () {
        $user = createUserWithRole('dev');

        $response = postJson(
            '/api/profile/dev',
            validDevProfilePayload(['cpf' => '123']),
            actingAsApi($user)
        );

        $response
            ->assertUnprocessable()
            ->assertJson([
                'error' => true,
            ])
            ->assertJsonStructure(['data' => ['cpf']]);

        expect(DevProfile::where('user_id', $user->id)->exists())->toBeFalse();
    });

    it('rejects creation when seniority level is invalid', function () {
        $user = createUserWithRole('dev');

        $response = postJson(
            '/api/profile/dev',
            validDevProfilePayload(['seniority_level' => 'principal']),
            actingAsApi($user)
        );

        $response
            ->assertUnprocessable()
            ->assertJson([
                'error' => true,
            ])
            ->assertJsonStructure(['data' => ['seniority_level']]);

        expect(DevProfile::where('user_id', $user->id)->exists())->toBeFalse();
    });
});
