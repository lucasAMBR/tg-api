<?php

use App\Models\DevSoftSkill;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\postJson;

beforeEach(function () {
    seedRolesAndPermissions();
    seedSoftSkillsCatalog();
});

describe('POST /api/soft-skill/dev', function () {
    it('allows a dev to register their soft skills for the first time successfully', function () {
        [$user, $devProfile] = createDevUserWithProfile();

        $payload = buildDevSoftSkillsPayload(evaluationWeightPerSkill: 2);

        $response = postJson('/api/soft-skill/dev', $payload, actingAsApi($user));

        $response
            ->assertOk()
            ->assertJson([
                'error' => false,
                'message' => 'Soft skill registered with success!',
            ])
            ->assertJsonCount(10, 'data');

        assertDatabaseCount('dev_soft_skill', 10);

        expect(DevSoftSkill::where('dev_profile_id', $devProfile->id)->count())->toBe(10);
    });

    it('denies registration when the user is not a dev', function () {
        $client = createUserWithRole('client');

        $response = postJson(
            '/api/soft-skill/dev',
            buildDevSoftSkillsPayload(evaluationWeightPerSkill: 2),
            actingAsApi($client)
        );

        $response
            ->assertForbidden()
            ->assertJson([
                'error' => true,
                'message' => 'Acesso negado.',
            ]);

        expect(DevSoftSkill::count())->toBe(0);
    });

    it('rejects registration when total points exceed the seniority limit', function () {
        [$user, $devProfile] = createDevUserWithProfile();

        $response = postJson(
            '/api/soft-skill/dev',
            buildDevSoftSkillsPayload(evaluationWeightPerSkill: 5),
            actingAsApi($user)
        );

        $response
            ->assertStatus(500)
            ->assertJson([
                'error' => true,
                'message' => 'You cannot exceed the pontuation limit based on your seniority level!',
            ]);

        expect(DevSoftSkill::where('dev_profile_id', $devProfile->id)->count())->toBe(0);
    });
});
