<?php

use App\Enums\DevJobVacancyStatusEnum;
use App\Enums\JobVacancyStatusEnum;
use App\Enums\SelectionProcessStageEnum;
use App\Events\ChatMessageSent;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\CompanyProfile;
use App\Models\DevJobVacancy;
use App\Models\JobVacancy;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patchJson;

beforeEach(function () {
    seedRolesAndPermissions();

    // As notificações do avanço de etapa disparam a tradução, que chama a API externa
    Bus::fake();
});

/**
 * Vaga com as inscrições encerradas e o processo seletivo parado na etapa informada
 *
 * @param list<SelectionProcessStageEnum> $steps
 */
function createVacancyInStep(User $company, array $steps, SelectionProcessStageEnum $currentStep): JobVacancy
{
    $companyProfile = CompanyProfile::create([
        'user_id' => $company->id,
        'name' => 'Acme',
        'bio' => 'Empresa de tecnologia.',
        'phone' => '+5531911112222',
        'cnpj' => '11222333000181',
        'founding_date' => '2010-01-01',
        'operational_segment' => 'technology',
    ]);

    $jobVacancy = JobVacancy::create([
        'title' => 'Desenvolvedor Backend',
        'description' => 'Vaga para desenvolvedor backend.',
        'employment_type' => 'remote',
        'benefits' => ['Vale refeição'],
        'estimated_salary' => 5000,
        'contract_type' => 'clt',
        'seniority_level' => 'junior',
        'status' => JobVacancyStatusEnum::CLOSED_INSCRIPTIONS->value,
        'process_step' => $currentStep->value,
        'company_profile_id' => $companyProfile->id,
    ]);

    foreach ($steps as $order => $step) {
        $jobVacancy->processSteps()->create(['step' => $step->value, 'order' => $order + 1]);
    }

    return $jobVacancy;
}

function applyInStep(JobVacancy $jobVacancy, string $devProfileId, SelectionProcessStageEnum $step): DevJobVacancy
{
    return DevJobVacancy::create([
        'dev_profile_id' => $devProfileId,
        'job_vacancy_id' => $jobVacancy->id,
        'status' => DevJobVacancyStatusEnum::IN_PROGRESS->value,
        'process_step' => $step->value,
    ]);
}

describe('PATCH /api/dev-vacancy/{job_vacancy_id}/advance-step', function () {
    it('sends the hiring chat message from the company to each dev approved in the last step', function () {
        Event::fake([ChatMessageSent::class]);

        $company = createUserWithRole('company');
        [$approvedDev, $approvedProfile] = createDevUserWithProfile(['name' => 'Maria Souza']);
        [$rejectedDev, $rejectedProfile] = createDevUserWithProfile(['cpf' => '11144477735', 'phone' => '+5531988887777']);

        $lastStep = SelectionProcessStageEnum::RESUME_SCREENING;
        $jobVacancy = createVacancyInStep($company, [$lastStep], $lastStep);

        $approvedApply = applyInStep($jobVacancy, $approvedProfile->id, $lastStep);
        applyInStep($jobVacancy, $rejectedProfile->id, $lastStep);

        actingAs($company, 'api');

        patchJson("/api/dev-vacancy/{$jobVacancy->id}/advance-step", ['apply_ids' => [$approvedApply->id]])
            ->assertOk()
            ->assertJsonPath('data.process_step', null);

        expect($approvedApply->fresh()->status)->toBe(DevJobVacancyStatusEnum::APPROVED);

        $conversation = ChatConversation::query()->ofParticipant($approvedDev->id)->sole();

        expect($conversation->otherParticipantId($approvedDev->id))->toBe($company->id)
            ->and($conversation->last_message_at)->not->toBeNull();

        $message = $conversation->messages()->sole();

        expect($message->sender_id)->toBe($company->id)
            ->and($message->body)->toContain('Maria Souza')
            ->and($message->body)->toContain($jobVacancy->title)
            ->and($message->body)->toContain('documentação');

        expect(ChatConversation::query()->ofParticipant($rejectedDev->id)->exists())->toBeFalse();

        Event::assertDispatchedTimes(ChatMessageSent::class, 1);
        Event::assertDispatched(ChatMessageSent::class, fn (ChatMessageSent $event) =>
            $event->message->is($message) && $event->recipientId === $approvedDev->id
        );
    });

    it('reuses the conversation that already exists between the company and the dev', function () {
        $company = createUserWithRole('company');
        [$dev, $devProfile] = createDevUserWithProfile();

        [$userOneId, $userTwoId] = collect([$company->id, $dev->id])->sort()->values()->all();
        $existing = ChatConversation::create(['user_one_id' => $userOneId, 'user_two_id' => $userTwoId]);

        $lastStep = SelectionProcessStageEnum::RESUME_SCREENING;
        $jobVacancy = createVacancyInStep($company, [$lastStep], $lastStep);
        $apply = applyInStep($jobVacancy, $devProfile->id, $lastStep);

        actingAs($company, 'api');

        patchJson("/api/dev-vacancy/{$jobVacancy->id}/advance-step", ['apply_ids' => [$apply->id]])
            ->assertOk();

        expect(ChatConversation::count())->toBe(1)
            ->and($existing->messages()->count())->toBe(1);
    });

    it('does not send the hiring message when there is a next step', function () {
        Event::fake([ChatMessageSent::class]);

        $company = createUserWithRole('company');
        [, $devProfile] = createDevUserWithProfile();

        $jobVacancy = createVacancyInStep(
            $company,
            [SelectionProcessStageEnum::RESUME_SCREENING, SelectionProcessStageEnum::INTERVIEW],
            SelectionProcessStageEnum::RESUME_SCREENING
        );
        $apply = applyInStep($jobVacancy, $devProfile->id, SelectionProcessStageEnum::RESUME_SCREENING);

        actingAs($company, 'api');

        patchJson("/api/dev-vacancy/{$jobVacancy->id}/advance-step", ['apply_ids' => [$apply->id]])
            ->assertOk()
            ->assertJsonPath('data.process_step', SelectionProcessStageEnum::INTERVIEW->value);

        expect(ChatMessage::count())->toBe(0);
        Event::assertNotDispatched(ChatMessageSent::class);
    });
});
