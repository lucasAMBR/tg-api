<?php

use Illuminate\Support\Facades\Bus;
use function Pest\Laravel\getJson;

beforeEach(function () {
    seedRolesAndPermissions();

    // As notificações disparam a tradução (API externa) e a vaga gera o embedding em fila
    Bus::fake();
});

describe('GET /api/search (freelance_job_vacancies)', function () {
    it('lists only the open freelance job vacancies, with the developer state', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$dev, $devProfile] = createFreelanceDevUser();
        [, $contractedDevProfile] = createFreelanceDevUser();

        $openVacancy = createFreelanceJobVacancy($clientProfile, ['title' => 'Landing page em Next.js']);
        createFreelanceProposal($openVacancy, $devProfile);
        createContractedFreelance($clientProfile, $contractedDevProfile, ['title' => 'Loja em Next.js']);

        getJson('/api/search?search=Next', actingAsApi($dev))
            ->assertOk()
            ->assertJsonPath('data.freelance_job_vacancies.pagination.total', 1)
            ->assertJsonPath('data.freelance_job_vacancies.data.0.id', $openVacancy->id)
            ->assertJsonPath('data.freelance_job_vacancies.data.0.my_proposal.status', 'pending')
            ->assertJsonPath('data.freelance_job_vacancies.data.0.is_invited', false)
            ->assertJsonMissingPath('data.freelance_job_vacancies.data.0.budget');
    });

    it('shows the budget only to the client who owns the vacancy', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        createFreelanceJobVacancy($clientProfile);

        getJson('/api/search', actingAsApi($client))
            ->assertOk()
            ->assertJsonPath('data.freelance_job_vacancies.data.0.budget', '10000.00');
    });

    it('returns an empty freelance group for companies', function () {
        [, $clientProfile] = createClientUserWithProfile();
        createFreelanceJobVacancy($clientProfile);

        getJson('/api/search', actingAsApi(createUserWithRole('company')))
            ->assertOk()
            ->assertJsonPath('data.freelance_job_vacancies.pagination.total', 0)
            ->assertJsonCount(0, 'data.freelance_job_vacancies.data');
    });
});
