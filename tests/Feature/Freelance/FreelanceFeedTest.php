<?php

use App\Models\FreelanceFeedPost;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    seedRolesAndPermissions();

    // As notificações disparam a tradução, que chama a API externa
    Bus::fake();

    Storage::fake('local');
});

/**
 * Cliente, dev contratado e o freela em andamento entre os dois
 *
 * @return array{0: \App\Models\User, 1: \App\Models\ClientProfile, 2: \App\Models\User, 3: \App\Models\DevProfile, 4: \App\Models\FreelanceJobVacancy}
 */
function freelanceContractParties(): array
{
    [$client, $clientProfile] = createClientUserWithProfile(['name' => 'Maria Cliente']);
    [$dev, $devProfile] = createFreelanceDevUser(['name' => 'João Dev']);

    return [$client, $clientProfile, $dev, $devProfile, createContractedFreelance($clientProfile, $devProfile)];
}

function createFeedPost(string $freelanceJobVacancyId, string $userId, array $overrides = []): FreelanceFeedPost
{
    return FreelanceFeedPost::create(array_merge([
        'freelance_job_vacancy_id' => $freelanceJobVacancyId,
        'user_id' => $userId,
        'type' => 'update',
        'content' => 'Atualização do andamento.',
    ], $overrides));
}

describe('access', function () {
    it('only exists after the contract is closed', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        $freelanceJobVacancy = createFreelanceJobVacancy($clientProfile);

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", actingAsApi($client))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'The freelance feed is available after the contract is closed!');
    });

    it('is only for the client and the contracted developer', function () {
        [, , , , $freelanceJobVacancy] = freelanceContractParties();
        [$otherClient] = createClientUserWithProfile();
        [$otherDev] = createFreelanceDevUser();

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", actingAsApi($otherClient))
            ->assertForbidden();

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", actingAsApi($otherDev))
            ->assertForbidden();

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", ['type' => 'update', 'content' => 'Oi'], actingAsApi($otherDev))
            ->assertForbidden();
    });
});

describe('GET /api/freelance-job-vacancy/{id}/feed', function () {
    it('lists the posts of both sides, newest first, filtering by type', function () {
        [$client, , $dev, , $freelanceJobVacancy] = freelanceContractParties();

        $update = createFeedPost($freelanceJobVacancy->id, $client->id);
        $this->travel(1)->minutes();
        $deliverable = createFeedPost($freelanceJobVacancy->id, $dev->id, ['type' => 'deliverable', 'content' => 'Primeira versão']);

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", actingAsApi($dev))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.data.0.id', $deliverable->id)
            ->assertJsonPath('data.data.0.author.name', 'João Dev')
            ->assertJsonPath('data.data.0.latest_review', null)
            ->assertJsonPath('data.data.1.id', $update->id)
            ->assertJsonPath('data.data.1.author.name', 'Maria Cliente')
            ->assertJsonMissingPath('data.data.1.latest_review');

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed?type=deliverable", actingAsApi($client))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.data.0.id', $deliverable->id);
    });
});

describe('POST /api/freelance-job-vacancy/{id}/feed', function () {
    it('lets the developer post a deliverable with documents and notifies the client', function () {
        [, $clientProfile, $dev, , $freelanceJobVacancy] = freelanceContractParties();

        $response = post("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", [
            'type' => 'deliverable',
            'content' => 'Segue o protótipo e a planilha de escopo.',
            'attachments' => [
                // Com conteúdo de PDF de verdade: a media library detecta o tipo pelo conteúdo
                UploadedFile::fake()->createWithContent('prototipo.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n"),
                UploadedFile::fake()->create('escopo.xlsx', 50, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
            ],
        ], array_merge(actingAsApi($dev), ['Accept' => 'application/json']));

        $response
            ->assertCreated()
            ->assertJsonPath('data.type', 'deliverable')
            ->assertJsonCount(2, 'data.attachments')
            ->assertJsonPath('data.attachments.0.name', 'prototipo.pdf')
            ->assertJsonPath('data.attachments.0.mime_type', 'application/pdf');

        expect($response->json('data.attachments.0.url'))->toBeString()->not->toBeEmpty();

        $media = FreelanceFeedPost::find($response->json('data.id'))->getFirstMedia(FreelanceFeedPost::ATTACHMENTS_COLLECTION);

        // Os documentos ficam no disco privado
        expect($media->disk)->toBe('local');
        Storage::disk('local')->assertExists($media->getPathRelativeToRoot());

        $notification = $clientProfile->notifications()->where('type', 'freelance_feed_post_created')->first();

        expect($notification)->not->toBeNull()
            ->and($notification->message)->toContain('João Dev publicou uma entrega');
    });

    it('only accepts document and image attachments', function () {
        [, , $dev, , $freelanceJobVacancy] = freelanceContractParties();

        post("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", [
            'type' => 'update',
            'attachments' => [UploadedFile::fake()->create('script.sh', 10, 'application/x-sh')],
        ], array_merge(actingAsApi($dev), ['Accept' => 'application/json']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['attachments.0'], 'data');
    });

    it('requires a text or an attachment', function () {
        [$client, , , , $freelanceJobVacancy] = freelanceContractParties();

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", ['type' => 'update'], actingAsApi($client))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['content'], 'data');
    });

    it('does not let the client post deliverables', function () {
        [$client, , , , $freelanceJobVacancy] = freelanceContractParties();

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", ['type' => 'deliverable', 'content' => 'Entrega'], actingAsApi($client))
            ->assertForbidden();
    });

    it('lets the client review a deliverable, which shows the latest verdict', function () {
        [$client, , $dev, $devProfile, $freelanceJobVacancy] = freelanceContractParties();
        $deliverable = createFeedPost($freelanceJobVacancy->id, $dev->id, ['type' => 'deliverable']);

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", [
            'type' => 'review',
            'parent_id' => $deliverable->id,
            'review_status' => 'changes_requested',
            'content' => 'Ajustar as cores do header.',
        ], actingAsApi($client))
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $deliverable->id)
            ->assertJsonPath('data.review_status', 'changes_requested');

        $this->travel(1)->minutes();

        $approval = postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", [
            'type' => 'review',
            'parent_id' => $deliverable->id,
            'review_status' => 'approved',
            'content' => 'Agora sim, aprovado!',
        ], actingAsApi($client))->assertCreated();

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed?type=deliverable", actingAsApi($dev))
            ->assertOk()
            ->assertJsonPath('data.data.0.latest_review.id', $approval->json('data.id'))
            ->assertJsonPath('data.data.0.latest_review.review_status', 'approved');

        expect($devProfile->notifications()->where('message', 'like', '%a aprovação de uma entrega%')->exists())->toBeTrue();
    });

    it('only reviews deliverables of the same feed', function () {
        [$client, , $dev, , $freelanceJobVacancy] = freelanceContractParties();
        $update = createFeedPost($freelanceJobVacancy->id, $dev->id);

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", [
            'type' => 'review',
            'parent_id' => $update->id,
            'review_status' => 'approved',
            'content' => 'Ok',
        ], actingAsApi($client))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'A review must reference a deliverable of this freelance job vacancy!');

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", [
            'type' => 'review',
            'content' => 'Sem entrega',
        ], actingAsApi($client))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['parent_id', 'review_status'], 'data');
    });

    it('does not let the developer review', function () {
        [, , $dev, , $freelanceJobVacancy] = freelanceContractParties();
        $deliverable = createFeedPost($freelanceJobVacancy->id, $dev->id, ['type' => 'deliverable']);

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", [
            'type' => 'review',
            'parent_id' => $deliverable->id,
            'review_status' => 'approved',
            'content' => 'Autoaprovado',
        ], actingAsApi($dev))
            ->assertForbidden();
    });

    it('becomes read-only when the freelance is completed', function () {
        [$client, $clientProfile] = createClientUserWithProfile();
        [, $devProfile] = createFreelanceDevUser();
        $freelanceJobVacancy = createContractedFreelance($clientProfile, $devProfile, ['status' => 'completed', 'completed_at' => now()]);
        createFeedPost($freelanceJobVacancy->id, $client->id);

        getJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", actingAsApi($client))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1);

        postJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed", ['type' => 'update', 'content' => 'Mais uma'], actingAsApi($client))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This freelance job vacancy is completed, so its feed is read-only!');
    });
});

describe('DELETE /api/freelance-job-vacancy/{id}/feed/{post_id}', function () {
    it('lets the author remove their own post only', function () {
        [$client, , $dev, , $freelanceJobVacancy] = freelanceContractParties();
        $clientPost = createFeedPost($freelanceJobVacancy->id, $client->id);
        $devPost = createFeedPost($freelanceJobVacancy->id, $dev->id);

        deleteJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed/{$clientPost->id}", [], actingAsApi($dev))
            ->assertForbidden();

        deleteJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed/{$devPost->id}", [], actingAsApi($dev))
            ->assertOk();

        expect($devPost->fresh()->trashed())->toBeTrue()
            ->and($clientPost->fresh()->trashed())->toBeFalse();
    });

    it('does not remove a post through another freelance feed', function () {
        [, , $dev, $devProfile, $freelanceJobVacancy] = freelanceContractParties();
        [, $otherClientProfile] = createClientUserWithProfile();
        $otherFreelance = createContractedFreelance($otherClientProfile, $devProfile);
        $post = createFeedPost($otherFreelance->id, $dev->id);

        deleteJson("/api/freelance-job-vacancy/{$freelanceJobVacancy->id}/feed/{$post->id}", [], actingAsApi($dev))
            ->assertNotFound();

        expect($post->fresh()->trashed())->toBeFalse();
    });
});
