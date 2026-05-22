<?php

use App\Models\EmploymentHistory;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

beforeEach(function () {
    seedRolesAndPermissions();
});

describe('POST /api/employment-history', function () {
    it('allows a dev user with profile to create an employment history successfully', function () {
        [$user, $devProfile] = createDevUserWithProfile();

        $payload = validEmploymentHistoryPayload();

        $response = postJson('/api/employment-history', $payload, actingAsApi($user));

        $response
            ->assertCreated()
            ->assertJson([
                'error' => false,
                'message' => 'Employment History item created with sucess!',
            ])
            ->assertJsonPath('data.company_name', $payload['company_name'])
            ->assertJsonPath('data.position_name', $payload['position_name'])
            ->assertJsonPath('data.dev_profile_id', $devProfile->id);

        assertDatabaseHas('employment_histories', [
            'dev_profile_id' => $devProfile->id,
            'company_name' => $payload['company_name'],
            'position_name' => $payload['position_name'],
            'start_date' => $payload['start_date'],
            'end_date' => $payload['end_date'],
        ]);
    });

    it('rejects actuation_details longer than the allowed limit', function () {
        [$user] = createDevUserWithProfile();

        $response = postJson(
            '/api/employment-history',
            validEmploymentHistoryPayload([
                'actuation_details' => str_repeat('a', 601),
            ]),
            actingAsApi($user)
        );

        $response
            ->assertUnprocessable()
            ->assertJson(['error' => true])
            ->assertJsonStructure(['data' => ['actuation_details']]);

        expect(EmploymentHistory::count())->toBe(0);
    });

    it('rejects end_date earlier than start_date', function () {
        [$user] = createDevUserWithProfile();

        $response = postJson(
            '/api/employment-history',
            validEmploymentHistoryPayload([
                'start_date' => '2022-06-01',
                'end_date' => '2021-01-01',
                'is_current' => false,
            ]),
            actingAsApi($user)
        );

        $response
            ->assertUnprocessable()
            ->assertJson(['error' => true])
            ->assertJsonStructure(['data' => ['end_date']]);

        expect(EmploymentHistory::count())->toBe(0);
    });

    it('denies creation when the user lacks permission', function () {
        $client = createUserWithRole('client');

        $response = postJson(
            '/api/employment-history',
            validEmploymentHistoryPayload(),
            actingAsApi($client)
        );

        $response
            ->assertForbidden()
            ->assertJson([
                'error' => true,
                'message' => 'Acesso negado.',
            ]);

        expect(EmploymentHistory::count())->toBe(0);
    });

    it('rejects creation when a required field is missing', function () {
        [$user] = createDevUserWithProfile();

        $payload = validEmploymentHistoryPayload();
        unset($payload['company_name']);

        $response = postJson('/api/employment-history', $payload, actingAsApi($user));

        $response
            ->assertUnprocessable()
            ->assertJson(['error' => true])
            ->assertJsonStructure(['data' => ['company_name']]);

        expect(EmploymentHistory::count())->toBe(0);
    });

    it('rejects creation when the dev user has no profile', function () {
        $user = createUserWithRole('dev');

        $response = postJson(
            '/api/employment-history',
            validEmploymentHistoryPayload(),
            actingAsApi($user)
        );

        $response
            ->assertStatus(500)
            ->assertJson([
                'error' => true,
                'message' => 'This action needs a existent profile to proceed!',
            ]);

        expect(EmploymentHistory::count())->toBe(0);
    });
});
