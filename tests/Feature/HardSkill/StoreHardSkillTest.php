<?php

use App\Enums\HardSkillLevelsEnum;
use App\Models\HardSkill;
use App\Models\Language;
use App\Services\HardSkill\HardSkillService;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertNotSoftDeleted;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
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
        [$otherUser] = createDevUserWithProfile(['cpf' => '11144477735', 'phone' => '+5531988887777']);

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
                'message' => 'This hard skill does not belong to your profile!',
            ]);

        assertDatabaseHas('hard_skills', [
            'id' => $hardSkill->id,
            'skill_level' => $hardSkill->skill_level,
        ]);
    });

    it('allows a dev to update their own hard skill', function () {
        [$user, $devProfile] = createDevUserWithProfile();

        $hardSkill = HardSkill::factory()->create([
            'dev_profile_id' => $devProfile->id,
            'language_id' => Language::first()->id,
        ]);

        $response = patchJson(
            "/api/hard-skill/{$hardSkill->id}",
            ['skill_level' => HardSkillLevelsEnum::EXPERT->value],
            actingAsApi($user)
        );

        $response
            ->assertOk()
            ->assertJsonPath('data.skill_level', HardSkillLevelsEnum::EXPERT->value);
    });
});

describe('GET /api/hard-skill', function () {
    it('lists only the hard skills of the given dev profile', function () {
        [$viewer] = createDevUserWithProfile();
        [, $ownerProfile] = createDevUserWithProfile(['cpf' => '11144477735', 'phone' => '+5531988887777']);
        [, $otherProfile] = createDevUserWithProfile(['cpf' => '39053344705', 'phone' => '+5531977776666']);

        [$firstLanguage, $secondLanguage] = Language::limit(2)->get();

        $ownerSkill = HardSkill::factory()->create([
            'dev_profile_id' => $ownerProfile->id,
            'language_id' => $firstLanguage->id,
        ]);

        HardSkill::factory()->create([
            'dev_profile_id' => $otherProfile->id,
            'language_id' => $secondLanguage->id,
        ]);

        $response = getJson(
            "/api/hard-skill?dev_profile_id={$ownerProfile->id}",
            actingAsApi($viewer)
        );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ownerSkill->id)
            ->assertJsonPath('data.0.language.id', $firstLanguage->id);
    });
});

describe('DELETE /api/hard-skill/{hardSkill}', function () {
    it('rejects when a dev tries to delete another dev hard skill', function () {
        [, $ownerProfile] = createDevUserWithProfile();
        [$otherUser] = createDevUserWithProfile(['cpf' => '11144477735', 'phone' => '+5531988887777']);

        $hardSkill = HardSkill::factory()->create([
            'dev_profile_id' => $ownerProfile->id,
            'language_id' => Language::first()->id,
        ]);

        $response = deleteJson(
            "/api/hard-skill/{$hardSkill->id}",
            [],
            actingAsApi($otherUser)
        );

        $response
            ->assertForbidden()
            ->assertJson([
                'error' => true,
                'message' => 'This hard skill does not belong to your profile!',
            ]);

        assertNotSoftDeleted('hard_skills', ['id' => $hardSkill->id]);
    });
});
