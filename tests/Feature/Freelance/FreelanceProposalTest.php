<?php

use App\Models\FreelanceProposal;
use Illuminate\Support\Facades\Bus;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    seedRolesAndPermissions();

    // As notificações disparam a tradução, que chama a API externa
    Bus::fake();
});

describe('POST /api/freelance-job-vacancy/{id}/proposals', function () {
    it('sends a fixed price proposal and notifies the client', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$dev, $devProfile] = createFreelanceDevUser(['name' => 'João Dev']);
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/proposals", [
            'pricing_type' => 'fixed',
            'total_price' => 10000,
            'message' => 'Entrego em duas semanas.',
        ], actingAsApi($dev))
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.pricing_type', 'fixed')
            ->assertJsonPath('data.total_price', '10000.00')
            ->assertJsonPath('data.hourly_rate', null)
            ->assertJsonPath('data.estimated_hours', null)
            ->assertJsonPath('data.dev_profile.id', $devProfile->id)
            ->assertJsonMissingPath('data.dev_profile.cpf');

        $notification = $clientProfile->notifications()->where('type', 'freelance_proposal_received')->first();

        expect($notification)->not->toBeNull()
            ->and($notification->message)->toContain('João Dev');
    });

    it('calculates the total of an hourly proposal and ignores the fixed price', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$dev] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/proposals", [
            'pricing_type' => 'hourly',
            'hourly_rate' => 120.5,
            'estimated_hours' => 40,
            'total_price' => 1,
        ], actingAsApi($dev))
            ->assertCreated()
            ->assertJsonPath('data.pricing_type', 'hourly')
            ->assertJsonPath('data.hourly_rate', '120.50')
            ->assertJsonPath('data.estimated_hours', 40)
            ->assertJsonPath('data.total_price', '4820.00');
    });

    it('requires the values of the chosen pricing type', function (array $payload, string $missing) {
        [, $clientProfile] = createClientUserWithProfile();
        [$dev] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/proposals", $payload, actingAsApi($dev))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$missing], 'data');
    })->with([
        'fixed without total' => [['pricing_type' => 'fixed', 'hourly_rate' => 100, 'estimated_hours' => 10], 'total_price'],
        'hourly without hours' => [['pricing_type' => 'hourly', 'hourly_rate' => 100], 'estimated_hours'],
        'hourly without rate' => [['pricing_type' => 'hourly', 'estimated_hours' => 10], 'hourly_rate'],
        'without pricing type' => [['total_price' => 100], 'pricing_type'],
    ]);

    it('accepts a single proposal per developer', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$dev, $devProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);
        createFreelanceProposal($freelanceJobVacancy, $devProfile);

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/proposals", [
            'pricing_type' => 'fixed',
            'total_price' => 8000,
        ], actingAsApi($dev))
            ->assertUnprocessable();

        expect(FreelanceProposal::count())->toBe(1);
    });

    it('does not take proposals after the contract is closed', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [, $hiredDevProfile] = createFreelanceDevUser();
        [$dev] = createFreelanceDevUser();
        $freelanceJobVacancy = createContractedFreelance($clientProfile, $hiredDevProfile);

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/proposals", [
            'pricing_type' => 'fixed',
            'total_price' => 8000,
        ], actingAsApi($dev))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This freelance job vacancy is not accepting proposals anymore!');
    });

    it('is only for developers', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/proposals", [
            'pricing_type' => 'fixed',
            'total_price' => 8000,
        ], actingAsApi($client))
            ->assertForbidden();
    });
});

describe('GET /api/freelance-job-vacancy/{id}/proposals', function () {
    it('lists the proposals of the vacancy to its owner', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        [, $devProfile] = createFreelanceDevUser();
        [, $otherDevProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        createFreelanceProposal($freelanceJobVacancy, $devProfile);
        $this->travel(1)->minutes();
        $newer = createFreelanceProposal($freelanceJobVacancy, $otherDevProfile, ['total_price' => 12000]);

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/proposals", actingAsApi($client))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.data.0.id', $newer->id)
            ->assertJsonPath('data.data.0.dev_profile.id', $otherDevProfile->id);
    });

    it('does not show the proposals to anyone else', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$otherClient] = createClientUserWithProfile();
        [$dev, $devProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);
        createFreelanceProposal($freelanceJobVacancy, $devProfile);

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/proposals", actingAsApi($otherClient))
            ->assertForbidden();

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/proposals", actingAsApi($dev))
            ->assertForbidden();
    });
});

describe('GET /api/freelance-proposal', function () {
    it('lists the proposals sent by the developer with their vacancies', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$dev, $devProfile] = createFreelanceDevUser();
        [, $otherDevProfile] = createFreelanceDevUser();

        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);
        $proposal = createFreelanceProposal($freelanceJobVacancy, $devProfile);
        createFreelanceProposal($freelanceJobVacancy, $otherDevProfile);

        // O orçamento de um freela removido some junto com ele
        $removed = createFreelanceJobVacancy($clientProfile);
        createFreelanceProposal($removed, $devProfile);
        $removed->delete();

        getJson('/api/freelance-proposal', actingAsApi($dev))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.data.0.id', $proposal->id)
            ->assertJsonPath('data.data.0.freelance_job_vacancy.id', $freelanceJobVacancy->id)
            ->assertJsonMissingPath('data.data.0.freelance_job_vacancy.budget');
    });
});

describe('PATCH /api/freelance-proposal/{id}', function () {
    it('lets the developer switch the pricing of a pending proposal', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$dev, $devProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);
        $proposal = createFreelanceProposal($freelanceJobVacancy, $devProfile, ['message' => 'Mensagem original']);

        patchJson("/api/freelance-proposal/{$proposal->id}", [
            'pricing_type' => 'hourly',
            'hourly_rate' => 100,
            'estimated_hours' => 30,
        ], actingAsApi($dev))
            ->assertOk()
            ->assertJsonPath('data.pricing_type', 'hourly')
            ->assertJsonPath('data.total_price', '3000.00')
            ->assertJsonPath('data.message', 'Mensagem original');
    });

    it('does not let a developer edit another developer proposal', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [, $devProfile] = createFreelanceDevUser();
        [$otherDev] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);
        $proposal = createFreelanceProposal($freelanceJobVacancy, $devProfile);

        patchJson("/api/freelance-proposal/{$proposal->id}", [
            'pricing_type' => 'fixed',
            'total_price' => 1,
        ], actingAsApi($otherDev))
            ->assertForbidden();
    });

    it('freezes the proposal once the client decides', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$dev, $devProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);
        $proposal = createFreelanceProposal($freelanceJobVacancy, $devProfile, ['status' => 'rejected']);

        patchJson("/api/freelance-proposal/{$proposal->id}", [
            'pricing_type' => 'fixed',
            'total_price' => 7000,
        ], actingAsApi($dev))
            ->assertUnprocessable();
    });
});

describe('DELETE /api/freelance-proposal/{id}', function () {
    it('lets the developer withdraw a pending proposal and send another one', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$dev, $devProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);
        $proposal = createFreelanceProposal($freelanceJobVacancy, $devProfile);

        deleteJson("/api/freelance-proposal/{$proposal->id}", [], actingAsApi($dev))
            ->assertOk();

        expect(FreelanceProposal::find($proposal->id))->toBeNull();

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/proposals", [
            'pricing_type' => 'fixed',
            'total_price' => 7000,
        ], actingAsApi($dev))
            ->assertCreated();
    });
});

describe('PATCH /api/freelance-proposal/{id}/accept', function () {
    it('closes the contract with the chosen proposal and rejects the others', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        [, $chosenDevProfile] = createFreelanceDevUser();
        [, $otherDevProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        $chosen = createFreelanceProposal($freelanceJobVacancy, $chosenDevProfile);
        $other = createFreelanceProposal($freelanceJobVacancy, $otherDevProfile);

        patchJson("/api/freelance-proposal/{$chosen->id}/accept", [], actingAsApi($client))
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $freelanceJobVacancy->refresh();

        expect($freelanceJobVacancy->status->value)->toBe('in_progress')
            ->and($freelanceJobVacancy->contracted_at)->not->toBeNull()
            ->and($other->fresh()->status->value)->toBe('rejected')
            ->and($chosenDevProfile->notifications()->where('type', 'freelance_proposal_accepted')->exists())->toBeTrue()
            ->and($otherDevProfile->notifications()->where('type', 'freelance_proposal_rejected')->exists())->toBeTrue();
    });

    it('closes a single contract per vacancy', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        [, $devProfile] = createFreelanceDevUser();
        [, $otherDevProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        $first = createFreelanceProposal($freelanceJobVacancy, $devProfile);
        $second = createFreelanceProposal($freelanceJobVacancy, $otherDevProfile);

        patchJson("/api/freelance-proposal/{$first->id}/accept", [], actingAsApi($client))->assertOk();

        patchJson("/api/freelance-proposal/{$second->id}/accept", [], actingAsApi($client))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This freelance job vacancy already has a closed contract!');
    });

    it('only lets the owner accept a proposal', function () {
        [, $clientProfile] = createClientUserWithProfile();
        [$otherClient] = createClientUserWithProfile();
        [$dev, $devProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);
        $proposal = createFreelanceProposal($freelanceJobVacancy, $devProfile);

        patchJson("/api/freelance-proposal/{$proposal->id}/accept", [], actingAsApi($otherClient))
            ->assertForbidden();

        patchJson("/api/freelance-proposal/{$proposal->id}/accept", [], actingAsApi($dev))
            ->assertForbidden();

        expect($proposal->fresh()->status->value)->toBe('pending');
    });
});

describe('POST /api/freelance-job-vacancy/{id}/invitations', function () {
    it('invites a developer to send a proposal and notifies them', function () {
        [$client, $clientProfile] = createClientUserWithProfile(['name' => 'Maria Cliente']);
        [$dev, $devProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/invitations", ['dev_profile_id' => $devProfile->id], actingAsApi($client))
            ->assertCreated()
            ->assertJsonPath('data.dev_profile.id', $devProfile->id);

        $notification = $devProfile->notifications()->where('type', 'freelance_invitation_received')->first();

        expect($notification)->not->toBeNull()
            ->and($notification->message)->toContain('Maria Cliente');

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}", actingAsApi($dev))
            ->assertJsonPath('data.is_invited', true);
    });

    it('does not invite the same developer twice or one that already sent a proposal', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        [, $devProfile] = createFreelanceDevUser();
        [, $proposingDevProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);
        createFreelanceProposal($freelanceJobVacancy, $proposingDevProfile);

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/invitations", ['dev_profile_id' => $devProfile->id], actingAsApi($client))
            ->assertCreated();

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/invitations", ['dev_profile_id' => $devProfile->id], actingAsApi($client))
            ->assertUnprocessable();

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/invitations", ['dev_profile_id' => $proposingDevProfile->id], actingAsApi($client))
            ->assertUnprocessable();
    });

    it('only invites for open vacancies of the authenticated client', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        [$otherClient] = createClientUserWithProfile();
        [, $hiredDevProfile] = createFreelanceDevUser();
        [, $devProfile] = createFreelanceDevUser();

        $open = createFreelanceJobVacancy($clientProfile);
        $contracted = createContractedFreelance($clientProfile, $hiredDevProfile);

        postJson("/api/freelance-job-vacancy/{$open->id}/invitations", ['dev_profile_id' => $devProfile->id], actingAsApi($otherClient))
            ->assertForbidden();

        postJson("/api/freelance-job-vacancy/{$contracted->id}/invitations", ['dev_profile_id' => $devProfile->id], actingAsApi($client))
            ->assertUnprocessable();
    });
});
