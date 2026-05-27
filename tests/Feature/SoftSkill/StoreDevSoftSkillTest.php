<?php

use App\Models\DevSoftSkill;
use function Pest\Laravel\postJson;

beforeEach(function () {
    seedRolesAndPermissions();
    seedSoftSkills();
});

describe('POST /api/soft-skill/dev', function () {
    it('denies creation when the user is not a dev and lacks permission', function () {
        $client = createUserWithRole('client');

        $response = postJson(
            '/api/soft-skill/dev',
            validDevSoftSkillPayload(),
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

    it('rejects soft skills when total points exceed the seniority limit', function () {
        [$user, $devProfile] = createDevUserWithProfile([
            'seniority_level' => 'junior',
        ]);

        $response = postJson(
            '/api/soft-skill/dev',
            validDevSoftSkillPayload(evaluationWeight: 5),
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
