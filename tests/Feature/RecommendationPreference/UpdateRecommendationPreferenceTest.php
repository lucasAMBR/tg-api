<?php

use App\Models\Language;
use App\Models\RecommendationPreference;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patchJson;

beforeEach(function () {
    seedRolesAndPermissions();
});

describe('PATCH /api/recommendation-preferences/{profile}', function () {
    it('allows a dev to update recommendation preferences successfully', function () {
        [$user, $devProfile] = createDevUserWithRecommendationPreference();

        $payload = validRecommendationPreferencePayload([
            'min_remuneration' => 7500,
            'on_site_job_radius' => 25,
            'hybrid_jobs_radius' => 50,
        ]);

        $response = patchJson(
            "/api/recommendation-preferences/{$devProfile->id}",
            $payload,
            actingAsApi($user)
        );

        $response
            ->assertOk()
            ->assertJson([
                'error' => false,
                'message' => 'Preferences updated!',
            ])
            ->assertJsonPath('data.allow_clt', true)
            ->assertJsonPath('data.min_remuneration', 7500)
            ->assertJsonPath('data.on_site_job_radius', 25);

        assertDatabaseHas('recommendation_preferences', [
            'dev_profile_id' => $devProfile->id,
            'allow_clt' => true,
            'on_site_job_radius' => 25,
            'hybrid_jobs_radius' => 50,
        ]);
    });

    it('rejects distance values below the minimum radius', function () {
        [$user, $devProfile] = createDevUserWithRecommendationPreference();

        $response = patchJson(
            "/api/recommendation-preferences/{$devProfile->id}",
            validRecommendationPreferencePayload([
                'on_site_job_radius' => 5,
                'hybrid_jobs_radius' => 0,
            ]),
            actingAsApi($user)
        );

        $response
            ->assertUnprocessable()
            ->assertJson(['error' => true])
            ->assertJsonStructure([
                'data' => ['on_site_job_radius', 'hybrid_jobs_radius'],
            ]);

        $preference = RecommendationPreference::where('dev_profile_id', $devProfile->id)->first();
        expect($preference->on_site_job_radius)->toBe(20);
        expect($preference->hybrid_jobs_radius)->toBe(40);
    });

    it('rejects when all employment type options are disabled', function () {
        [$user, $devProfile] = createDevUserWithRecommendationPreference();

        $response = patchJson(
            "/api/recommendation-preferences/{$devProfile->id}",
            validRecommendationPreferencePayload([
                'allow_clt' => false,
                'allow_contractor' => false,
                'allow_internship' => false,
            ]),
            actingAsApi($user)
        );

        $response
            ->assertUnprocessable()
            ->assertJson(['error' => true])
            ->assertJsonPath('data.employment_types.0', 'At least one employment type must be enabled.');

        assertDatabaseHas('recommendation_preferences', [
            'dev_profile_id' => $devProfile->id,
            'allow_clt' => true,
        ]);
    });

    it('rejects when minimum remuneration exceeds ten thousand', function () {
        [$user, $devProfile] = createDevUserWithRecommendationPreference();

        $response = patchJson(
            "/api/recommendation-preferences/{$devProfile->id}",
            validRecommendationPreferencePayload([
                'min_remuneration' => 15000,
            ]),
            actingAsApi($user)
        );

        $response
            ->assertUnprocessable()
            ->assertJson(['error' => true])
            ->assertJsonStructure(['data' => ['min_remuneration']]);

        expect(
            RecommendationPreference::where('dev_profile_id', $devProfile->id)->first()->min_remuneration
        )->not->toBe('15000.00');
    });

    it('rejects when all work mode options are disabled', function () {
        [$user, $devProfile] = createDevUserWithRecommendationPreference();

        $response = patchJson(
            "/api/recommendation-preferences/{$devProfile->id}",
            validRecommendationPreferencePayload([
                'allow_on_site' => false,
                'allow_hybrid' => false,
                'allow_remote' => false,
            ]),
            actingAsApi($user)
        );

        $response
            ->assertUnprocessable()
            ->assertJson(['error' => true])
            ->assertJsonPath('data.work_modes.0', 'At least one work mode must be enabled.');

        assertDatabaseHas('recommendation_preferences', [
            'dev_profile_id' => $devProfile->id,
            'allow_on_site' => true,
            'allow_remote' => true,
        ]);
    });

    it('rejects when the dev blacklists all available languages', function () {
        seedLanguagesCatalog();

        [$user, $devProfile] = createDevUserWithRecommendationPreference();
        $preference = RecommendationPreference::where('dev_profile_id', $devProfile->id)->first();

        $allLanguageIds = Language::pluck('id')->all();

        $response = patchJson(
            "/api/recommendation-preferences/{$devProfile->id}",
            validRecommendationPreferencePayload([
                'languages_blacklist' => $allLanguageIds,
            ]),
            actingAsApi($user)
        );

        $response
            ->assertUnprocessable()
            ->assertJson(['error' => true])
            ->assertJsonPath(
                'data.languages_blacklist.0',
                'You cannot blacklist all available languages.'
            );

        assertDatabaseCount('recommendation_preferences_black_list', 0);
        expect($preference->fresh()->blackListedLanguages)->toHaveCount(0);
    });
});
