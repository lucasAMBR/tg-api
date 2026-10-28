<?php

use App\Models\DevProfile;
use App\Models\DevProfileEmbedding;
use App\Models\FreelanceInvitation;
use App\Models\FreelanceJobVacancy;
use App\Models\FreelanceJobVacancyEmbedding;
use App\Models\Language;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use function Pest\Laravel\getJson;

beforeEach(function () {
    seedRolesAndPermissions();

    Bus::fake();
});

/**
 * As consultas de similaridade usam os operadores do pgvector, que o SQLite dos testes
 * não tem. Rode com DB_CONNECTION=pgsql apontando para um banco com a extensão vector
 */
function skipUnlessPgvector(): void
{
    if (DB::connection()->getDriverName() !== 'pgsql') {
        test()->markTestSkipped('Precisa do Postgres com pgvector.');
    }
}

/**
 * Vetor de embedding com as primeiras posições informadas e o resto zerado
 *
 * @return list<float>
 */
function embeddingVector(float ...$leading): array
{
    return array_pad($leading, 1536, 0.0);
}

function embedDevProfile(DevProfile $devProfile, array $vector): void
{
    DevProfileEmbedding::create(['dev_profile_id' => $devProfile->id, 'embedding' => $vector]);
}

function embedFreelanceJobVacancy(FreelanceJobVacancy $freelanceJobVacancy, array $vector): void
{
    FreelanceJobVacancyEmbedding::create(['freelance_job_vacancy_id' => $freelanceJobVacancy->id, 'embedding' => $vector]);
}

describe('GET /api/recommendation/freelance-job-vacancy/{id}/proposals', function () {
    it('ranks the pending proposals by profile similarity and price proximity to the budget', function () {
        skipUnlessPgvector();

        [$client, $clientProfile] = createClientUserWithProfile();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile, ['budget' => 10000]);
        embedFreelanceJobVacancy($freelanceJobVacancy, embeddingVector(1));

        // Perfil idêntico ao freela, mas cobrando o dobro do budget: 0,6 × 1 + 0,4 × 0,5 = 0,8
        [, $perfectFit] = createFreelanceDevUser();
        embedDevProfile($perfectFit, embeddingVector(1));
        $perfectFitProposal = createFreelanceProposal($freelanceJobVacancy, $perfectFit, ['total_price' => 20000]);

        // Perfil sem relação com o freela, no preço exato: 0,6 × 0 + 0,4 × 1 = 0,4
        [, $unrelated] = createFreelanceDevUser();
        embedDevProfile($unrelated, embeddingVector(0, 1));
        $unrelatedProposal = createFreelanceProposal($freelanceJobVacancy, $unrelated, ['total_price' => 10000]);

        // Meio caminho e 20% abaixo do budget: 0,6 × 0,7071 + 0,4 × 0,8 = 0,7443
        [, $partialFit] = createFreelanceDevUser();
        embedDevProfile($partialFit, embeddingVector(M_SQRT1_2, M_SQRT1_2));
        $partialFitProposal = createFreelanceProposal($freelanceJobVacancy, $partialFit, [
            'pricing_type' => 'hourly',
            'hourly_rate' => 100,
            'estimated_hours' => 80,
            'total_price' => 8000,
        ]);

        // Sem embedding ainda: entra só pelo preço, empatando com o perfil sem relação, que orçou antes
        $this->travel(1)->minutes();
        [, $withoutEmbedding] = createFreelanceDevUser();
        $withoutEmbeddingProposal = createFreelanceProposal($freelanceJobVacancy, $withoutEmbedding, ['total_price' => 10000]);

        // Orçamento já recusado fica fora do ranking
        [, $rejected] = createFreelanceDevUser();
        embedDevProfile($rejected, embeddingVector(1));
        createFreelanceProposal($freelanceJobVacancy, $rejected, ['total_price' => 10000, 'status' => 'rejected']);

        getJson("/api/recommendation/freelance-job-vacancy/{$freelanceJobVacancy->id}/proposals", actingAsApi($client))
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.proposal.id', $perfectFitProposal->id)
            ->assertJsonPath('data.0.score', 0.8)
            ->assertJsonPath('data.0.similarity', 1)
            ->assertJsonPath('data.0.price_score', 0.5)
            ->assertJsonPath('data.0.proposal.dev_profile.id', $perfectFit->id)
            ->assertJsonPath('data.1.proposal.id', $partialFitProposal->id)
            ->assertJsonPath('data.1.score', 0.7443)
            ->assertJsonPath('data.2.proposal.id', $unrelatedProposal->id)
            ->assertJsonPath('data.2.score', 0.4)
            ->assertJsonPath('data.3.proposal.id', $withoutEmbeddingProposal->id)
            ->assertJsonPath('data.3.similarity', null)
            ->assertJsonPath('data.3.score', 0.4);

        getJson("/api/recommendation/freelance-job-vacancy/{$freelanceJobVacancy->id}/proposals?limit=1", actingAsApi($client))
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('waits for the vacancy embedding', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        getJson("/api/recommendation/freelance-job-vacancy/{$freelanceJobVacancy->id}/proposals", actingAsApi($client))
            ->assertStatus(409)
            ->assertJsonPath('message', "This freelance job vacancy doesn't have an embedding yet!");
    });

    it('is only for the owner of the vacancy', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$otherClient] = createClientUserWithProfile();
        [$dev] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);
        embedFreelanceJobVacancy($freelanceJobVacancy, embeddingVector(1));

        getJson("/api/recommendation/freelance-job-vacancy/{$freelanceJobVacancy->id}/proposals", actingAsApi($otherClient))
            ->assertForbidden();

        getJson("/api/recommendation/freelance-job-vacancy/{$freelanceJobVacancy->id}/proposals", actingAsApi($dev))
            ->assertForbidden();
    });
});

describe('GET /api/recommendation/freelance-job-vacancy/{id}/devs', function () {
    it('recommends developers whether they sent a proposal or not, flagging proposals and invitations', function () {
        skipUnlessPgvector();

        [$client, $clientProfile] = createClientUserWithProfile();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);
        embedFreelanceJobVacancy($freelanceJobVacancy, embeddingVector(1));

        [, $proposed] = createFreelanceDevUser();
        embedDevProfile($proposed, embeddingVector(1));
        createFreelanceProposal($freelanceJobVacancy, $proposed);

        [, $invited] = createFreelanceDevUser();
        embedDevProfile($invited, embeddingVector(0, 1));
        FreelanceInvitation::create(['freelance_job_vacancy_id' => $freelanceJobVacancy->id, 'dev_profile_id' => $invited->id]);

        [, $untouched] = createFreelanceDevUser();
        embedDevProfile($untouched, embeddingVector(M_SQRT1_2, M_SQRT1_2));

        getJson("/api/recommendation/freelance-job-vacancy/{$freelanceJobVacancy->id}/devs", actingAsApi($client))
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.dev_profile.id', $proposed->id)
            ->assertJsonPath('data.0.has_proposal', true)
            ->assertJsonPath('data.0.is_invited', false)
            ->assertJsonMissingPath('data.0.dev_profile.cpf')
            ->assertJsonPath('data.1.dev_profile.id', $untouched->id)
            ->assertJsonPath('data.1.has_proposal', false)
            ->assertJsonPath('data.1.is_invited', false)
            ->assertJsonPath('data.2.dev_profile.id', $invited->id)
            ->assertJsonPath('data.2.is_invited', true);
    });

    it('waits for the vacancy embedding', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        getJson("/api/recommendation/freelance-job-vacancy/{$freelanceJobVacancy->id}/devs", actingAsApi($client))
            ->assertStatus(409);
    });
});

describe('GET /api/recommendation/freelance-job-vacancy', function () {
    it('recommends the open vacancies closest to the developer profile', function () {
        skipUnlessPgvector();

        [, $clientProfile] = createClientUserWithProfile();
        [$dev, $devProfile] = createFreelanceDevUser();
        embedDevProfile($devProfile, embeddingVector(1));

        $closest = createFreelanceJobVacancy($clientProfile, ['title' => 'Mais próximo']);
        embedFreelanceJobVacancy($closest, embeddingVector(1));

        $farthest = createFreelanceJobVacancy($clientProfile, ['title' => 'Mais distante']);
        embedFreelanceJobVacancy($farthest, embeddingVector(0, 1));
        FreelanceInvitation::create(['freelance_job_vacancy_id' => $farthest->id, 'dev_profile_id' => $devProfile->id]);

        // Fora da recomendação: já contratado, já orçado pelo dev e ainda sem embedding
        [, $otherDevProfile] = createFreelanceDevUser();
        $contracted = createContractedFreelance($clientProfile, $otherDevProfile);
        embedFreelanceJobVacancy($contracted, embeddingVector(1));

        $proposed = createFreelanceJobVacancy($clientProfile);
        embedFreelanceJobVacancy($proposed, embeddingVector(1));
        createFreelanceProposal($proposed, $devProfile);

        createFreelanceJobVacancy($clientProfile);

        getJson('/api/recommendation/freelance-job-vacancy', actingAsApi($dev))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.freelance_job_vacancy.id', $closest->id)
            ->assertJsonPath('data.0.similarity', 1)
            ->assertJsonPath('data.0.freelance_job_vacancy.is_invited', false)
            ->assertJsonMissingPath('data.0.freelance_job_vacancy.budget')
            ->assertJsonPath('data.1.freelance_job_vacancy.id', $farthest->id)
            ->assertJsonPath('data.1.freelance_job_vacancy.is_invited', true);

        getJson('/api/recommendation/freelance-job-vacancy?min_similarity=0.5', actingAsApi($dev))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.freelance_job_vacancy.id', $closest->id);
    });

    it('respects the language preferences of the developer', function () {
        skipUnlessPgvector();

        [, $clientProfile] = createClientUserWithProfile();
        [$dev, $devProfile] = createFreelanceDevUser();
        embedDevProfile($devProfile, embeddingVector(1));

        $php = Language::create(['name' => 'PHP', 'slug' => 'php', 'is_approved' => true]);
        $go = Language::create(['name' => 'Go', 'slug' => 'go', 'is_approved' => true]);
        $rust = Language::create(['name' => 'Rust', 'slug' => 'rust', 'is_approved' => true]);

        $devProfile->hard_skills()->create(['language_id' => $go->id, 'skill_level' => 'advanced']);

        $preference = $devProfile->recommendation_preference()->create(['allow_stack_flexibility' => false]);
        $preference->blackListedLanguages()->attach($php->id);

        $withoutStack = createFreelanceJobVacancy($clientProfile);
        embedFreelanceJobVacancy($withoutStack, embeddingVector(1));

        $knownStack = createFreelanceJobVacancy($clientProfile, ['job_type' => 'development']);
        $knownStack->languages()->attach($go->id, ['language_level' => 'advanced']);
        embedFreelanceJobVacancy($knownStack, embeddingVector(1));

        $blackListed = createFreelanceJobVacancy($clientProfile, ['job_type' => 'development']);
        $blackListed->languages()->attach([$go->id => ['language_level' => 'basic'], $php->id => ['language_level' => 'basic']]);
        embedFreelanceJobVacancy($blackListed, embeddingVector(1));

        $unknownStack = createFreelanceJobVacancy($clientProfile, ['job_type' => 'development']);
        $unknownStack->languages()->attach($rust->id, ['language_level' => 'basic']);
        embedFreelanceJobVacancy($unknownStack, embeddingVector(1));

        $ids = collect(getJson('/api/recommendation/freelance-job-vacancy', actingAsApi($dev))->assertOk()->json('data'))
            ->pluck('freelance_job_vacancy.id');

        expect($ids)->toHaveCount(2)
            ->toContain($withoutStack->id, $knownStack->id);
    });

    it('waits for the developer embedding', function () {
        [$dev] = createFreelanceDevUser();

        getJson('/api/recommendation/freelance-job-vacancy', actingAsApi($dev))
            ->assertStatus(409)
            ->assertJsonPath('message', "Your profile doesn't have an embedding yet!");
    });

    it('is only for developers', function () {
        [$client] = createClientUserWithProfile();

        getJson('/api/recommendation/freelance-job-vacancy', actingAsApi($client))
            ->assertForbidden();
    });
});
