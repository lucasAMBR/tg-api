<?php

use App\Enums\HardSkillLevelsEnum;
use App\Models\HardSkill;
use App\Models\Language;
use App\Services\HardSkill\HardSkillService;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    seedRolesAndPermissions();
    seedLanguagesCatalog();
});

describe('POST /api/hard-skill', function () {
    it('allows a dev to register a hard skill successfully', function () {
        [$user, $devProfile] = createDevUserWithProfile();
        $language = Language::first();

        $payload = validHardSkillPayload($language->id, [
            'skill_level' => HardSkillLevelsEnum::ADVANCED->value,
        ]);

        $response = postJson('/api/hard-skill', $payload, actingAsApi($user));

        $response
            ->assertCreated()
            ->assertJson([
                'error' => false,
                'message' => 'Hard skill registered with success!',
            ])
            ->assertJsonPath('data.skill_level', $payload['skill_level'])
            ->assertJsonPath('data.dev_profile_id', $devProfile->id);

        assertDatabaseHas('hard_skills', [
            'dev_profile_id' => $devProfile->id,
            'language_id' => $language->id,
            'skill_level' => $payload['skill_level'],
        ]);
    });

    it('denies registration when the user lacks permission', function () {
        $client = createUserWithRole('client');
        $language = Language::first();

        $response = postJson(
            '/api/hard-skill',
            validHardSkillPayload($language->id),
            actingAsApi($client)
        );

        $response
            ->assertForbidden()
            ->assertJson([
                'error' => true,
                'message' => 'Acesso negado.',
            ]);

        expect(HardSkill::count())->toBe(0);
    });

    it('rejects registration when the dev already has ten hard skills', function () {
        [$user, $devProfile] = createDevUserWithProfile();

        $languages = Language::limit(10)->get();

        foreach ($languages as $language) {
            HardSkill::factory()->create([
                'dev_profile_id' => $devProfile->id,
                'language_id' => $language->id,
            ]);
        }

        $eleventhLanguage = Language::skip(10)->first();

        $response = postJson(
            '/api/hard-skill',
            validHardSkillPayload($eleventhLanguage->id),
            actingAsApi($user)
        );

        $response
            ->assertStatus(500)
            ->assertJson([
                'error' => true,
                'message' => 'You cannot register more than 10 hard skills.',
            ]);

        assertDatabaseCount('hard_skills', 10);
        expect($devProfile->hard_skills()->count())->toBe(HardSkillService::MAX_HARD_SKILLS_PER_PROFILE);
    });
});

describe('PATCH /api/hard-skill/{hardSkill}', function () {
    it('rejects when a dev tries to update another dev hard skill', function () {
        [, $ownerProfile] = createDevUserWithProfile();
        [$otherUser] = createDevUserWithProfile();

        $language = Language::first();

        $hardSkill = HardSkill::factory()->create([
            'dev_profile_id' => $ownerProfile->id,
            'language_id' => $language->id,
        ]);

        $response = patchJson(
            "/api/hard-skill/{$hardSkill->id}",
            ['skill_level' => HardSkillLevelsEnum::EXPERT->value],
            actingAsApi($otherUser)
        );

        $response
            ->assertForbidden()
            ->assertJson([
                'error' => true,
                'message' => 'Acesso negado.',
            ]);

        assertDatabaseHas('hard_skills', [
            'id' => $hardSkill->id,
            'skill_level' => $hardSkill->skill_level,
        ]);
    });
});
