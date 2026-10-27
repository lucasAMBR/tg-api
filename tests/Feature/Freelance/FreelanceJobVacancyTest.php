<?php

use App\Models\FreelanceInvitation;
use Illuminate\Support\Facades\Bus;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    seedRolesAndPermissions();

    // As notificações disparam a tradução (API externa) e a vaga gera o embedding em fila
    Bus::fake();
});

function validFreelanceJobVacancyPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Landing page em Next.js',
        'description' => 'Landing page institucional com formulário de contato.',
        'job_type' => 'design',
        'budget' => 10000,
        'seniority_level' => 'mid_level',
        'specialties' => ['frontend'],
    ], $overrides);
}

describe('POST /api/freelance-job-vacancy', function () {
    it('creates an open vacancy whose budget only the owner sees', function () {
        [$client, $clientProfile] = createClientUserWithProfile();

        postJson('/api/freelance-job-vacancy', validFreelanceJobVacancyPayload(), actingAsApi($client))
            ->assertCreated()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.budget', '10000.00')
            ->assertJsonPath('data.proposals_count', 0)
            ->assertJsonPath('data.contract', null)
            ->assertJsonPath('data.profile.id', $clientProfile->id)
            ->assertJsonMissingPath('data.profile.cpf')
            ->assertJsonMissingPath('data.profile.phone')
            ->assertJsonMissingPath('data.salary_type')
            ->assertJsonMissingPath('data.estimated_salary');
    });

    it('requires a positive budget', function (mixed $budget) {
        [$client] = createClientUserWithProfile();

        postJson('/api/freelance-job-vacancy', validFreelanceJobVacancyPayload(['budget' => $budget]), actingAsApi($client))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['budget'], 'data');
    })->with([null, 0, -10]);

    it('only accepts developer specialties', function () {
        [$client] = createClientUserWithProfile();

        postJson('/api/freelance-job-vacancy', validFreelanceJobVacancyPayload(['specialties' => ['marketing']]), actingAsApi($client))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['specialties.0'], 'data');
    });

    it('still requires a stack for job types that depend on one', function () {
        [$client] = createClientUserWithProfile();

        postJson('/api/freelance-job-vacancy', validFreelanceJobVacancyPayload(['job_type' => 'development']), actingAsApi($client))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Vagas do tipo selecionado exigem ao menos uma linguagem ou stack.');
    });
});

describe('GET /api/freelance-job-vacancy/{id}', function () {
    it('hides the budget and the proposals count from developers', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$dev] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}", actingAsApi($dev))
            ->assertOk()
            ->assertJsonMissingPath('data.budget')
            ->assertJsonMissingPath('data.proposals_count')
            ->assertJsonMissingPath('data.contract')
            ->assertJsonPath('data.my_proposal', null)
            ->assertJsonPath('data.is_invited', false);
    });

    it('shows the developer their own proposal and invitation', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$dev, $devProfile] = createFreelanceDevUser();
        [, $otherDevProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        $proposal = createFreelanceProposal($freelanceJobVacancy, $devProfile);
        createFreelanceProposal($freelanceJobVacancy, $otherDevProfile);
        FreelanceInvitation::create(['freelance_job_vacancy_id' => $freelanceJobVacancy->id, 'dev_profile_id' => $devProfile->id]);

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}", actingAsApi($dev))
            ->assertOk()
            ->assertJsonPath('data.my_proposal.id', $proposal->id)
            ->assertJsonPath('data.is_invited', true);
    });

    it('shows the contract only to its two sides', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        [$hiredDev, $hiredDevProfile] = createFreelanceDevUser();
        [$otherDev] = createFreelanceDevUser();
        $freelanceJobVacancy = createContractedFreelance($clientProfile, $hiredDevProfile);

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}", actingAsApi($client))
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.contract.dev_profile.id', $hiredDevProfile->id)
            ->assertJsonMissingPath('data.contract.dev_profile.cpf');

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}", actingAsApi($hiredDev))
            ->assertOk()
            ->assertJsonPath('data.contract.dev_profile.id', $hiredDevProfile->id);

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}", actingAsApi($otherDev))
            ->assertOk()
            ->assertJsonMissingPath('data.contract');
    });
});

describe('GET /api/freelance-job-vacancy', function () {
    it('lists only the open vacancies', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$dev, $devProfile] = createFreelanceDevUser();

        $open = createFreelanceJobVacancy($clientProfile);
        createContractedFreelance($clientProfile, $devProfile);
        createFreelanceJobVacancy($clientProfile, ['status' => 'completed']);

        getJson('/api/freelance-job-vacancy', actingAsApi($dev))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.data.0.id', $open->id);
    });

    it('filters the vacancies the developer was invited to', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$dev, $devProfile] = createFreelanceDevUser();

        $invited = createFreelanceJobVacancy($clientProfile);
        createFreelanceJobVacancy($clientProfile);
        FreelanceInvitation::create(['freelance_job_vacancy_id' => $invited->id, 'dev_profile_id' => $devProfile->id]);

        getJson('/api/freelance-job-vacancy?invited=true', actingAsApi($dev))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.data.0.id', $invited->id)
            ->assertJsonPath('data.data.0.is_invited', true);
    });

    it('does not let companies browse freelance vacancies', function () {
        getJson('/api/freelance-job-vacancy', actingAsApi(createUserWithRole('company')))
            ->assertForbidden();
    });
});

describe('GET /api/freelance-job-vacancy/my-vacancies', function () {
    it('lists the client vacancies in every status with their proposals count', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        [, $otherClientProfile] = createClientUserWithProfile();
        [, $devProfile] = createFreelanceDevUser();
        [, $otherDevProfile] = createFreelanceDevUser();

        $open = createFreelanceJobVacancy($clientProfile);
        createFreelanceProposal($open, $devProfile);
        createFreelanceProposal($open, $otherDevProfile);

        $this->travel(1)->minutes();
        $contracted = createContractedFreelance($clientProfile, $devProfile);

        createFreelanceJobVacancy($otherClientProfile);

        getJson('/api/freelance-job-vacancy/my-vacancies', actingAsApi($client))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.data.0.id', $contracted->id)
            ->assertJsonPath('data.data.0.status', 'in_progress')
            ->assertJsonPath('data.data.0.contract.dev_profile.id', $devProfile->id)
            ->assertJsonPath('data.data.1.id', $open->id)
            ->assertJsonPath('data.data.1.status', 'open')
            ->assertJsonPath('data.data.1.proposals_count', 2)
            ->assertJsonPath('data.data.1.budget', '10000.00');

        getJson('/api/freelance-job-vacancy/my-vacancies?status=open', actingAsApi($client))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.data.0.id', $open->id);
    });

    it('is only for clients', function () {
        [$dev] = createFreelanceDevUser();

        getJson('/api/freelance-job-vacancy/my-vacancies', actingAsApi($dev))
            ->assertForbidden()
            ->assertJsonPath('message', 'You must be a client for manage a freelance job vacancy');
    });
});

describe('GET /api/freelance-job-vacancy/my-contracts', function () {
    it('lists the vacancies the developer was hired for, the ones in progress first', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$dev, $devProfile] = createFreelanceDevUser();
        [, $otherDevProfile] = createFreelanceDevUser();

        $inProgress = createContractedFreelance($clientProfile, $devProfile);
        $this->travel(1)->minutes();
        $completed = createContractedFreelance($clientProfile, $devProfile, ['status' => 'completed', 'completed_at' => now()]);

        // Orçou mas não foi o escolhido, e outro freela fechado com outro dev
        $lost = createFreelanceJobVacancy($clientProfile, ['status' => 'in_progress']);
        createFreelanceProposal($lost, $devProfile, ['status' => 'rejected']);
        createFreelanceProposal($lost, $otherDevProfile, ['status' => 'accepted']);

        getJson('/api/freelance-job-vacancy/my-contracts', actingAsApi($dev))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.data.0.id', $inProgress->id)
            ->assertJsonPath('data.data.0.contract.dev_profile_id', $devProfile->id)
            ->assertJsonPath('data.data.1.id', $completed->id);

        getJson('/api/freelance-job-vacancy/my-contracts?status=completed', actingAsApi($dev))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.data.0.id', $completed->id);
    });

    it('is only for developers', function () {
        [$client] = createClientUserWithProfile();

        getJson('/api/freelance-job-vacancy/my-contracts', actingAsApi($client))
            ->assertForbidden();
    });
});

describe('PATCH /api/freelance-job-vacancy/{id}', function () {
    it('updates the budget of an open vacancy', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        patchJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}", ['budget' => 12500.5], actingAsApi($client))
            ->assertOk()
            ->assertJsonPath('data.budget', '12500.50');
    });

    it('does not edit a vacancy after the contract is closed', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        [, $devProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createContractedFreelance($clientProfile, $devProfile);

        patchJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}", ['budget' => 500], actingAsApi($client))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Only open freelance job vacancies can be edited!');
    });
});

describe('DELETE /api/freelance-job-vacancy/{id}', function () {
    it('removes an open vacancy', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        deleteJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}", [], actingAsApi($client))
            ->assertOk();

        expect($freelanceJobVacancy->fresh()->trashed())->toBeTrue();

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}", actingAsApi($client))
            ->assertUnprocessable();
    });

    it('does not remove a vacancy after the contract is closed', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        [, $devProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createContractedFreelance($clientProfile, $devProfile);

        deleteJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}", [], actingAsApi($client))
            ->assertUnprocessable();

        expect($freelanceJobVacancy->fresh()->trashed())->toBeFalse();
    });
});

describe('PATCH /api/freelance-job-vacancy/{id}/complete', function () {
    it('completes the contract and notifies the developer', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        [, $devProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createContractedFreelance($clientProfile, $devProfile);

        patchJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/complete", [], actingAsApi($client))
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        expect($freelanceJobVacancy->fresh()->completed_at)->not->toBeNull()
            ->and($devProfile->notifications()->where('type', 'freelance_job_vacancy_completed')->exists())->toBeTrue();
    });

    it('only completes vacancies in progress', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        patchJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/complete", [], actingAsApi($client))
            ->assertUnprocessable();
    });

    it('only lets the owner complete the contract', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$otherClient] = createClientUserWithProfile();
        [, $devProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createContractedFreelance($clientProfile, $devProfile);

        patchJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/complete", [], actingAsApi($otherClient))
            ->assertForbidden();
    });
});
