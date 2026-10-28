<?php

use App\Events\ChatMessageSent;
use App\Events\ChatMessagesRead;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    seedRolesAndPermissions();
});

/**
 * Os testes do chat alternam entre usuários na mesma requisição de teste, e o JWTGuard
 * guarda o usuário (e o token) da primeira requisição. O actingAs troca o usuário do guard
 */
function asChatUser(User $user): void
{
    actingAs($user, 'api');
}

function openChatConversation(User $user, User $other): string
{
    asChatUser($user);

    return postJson('/api/chat', ['user_id' => $other->id])->json('data.id');
}

function sendChatMessage(User $user, string $conversationId, string $body = 'Olá!'): string
{
    asChatUser($user);

    return postJson("/api/chat/{$conversationId}/messages", ['body' => $body])->json('data.id');
}

/**
 * @return list<string>
 */
function broadcastChannelNames(object $event): array
{
    return collect($event->broadcastOn())->map(fn ($channel) => $channel->name)->all();
}

describe('POST /api/chat', function () {
    it('opens a single conversation between users of different roles, whoever opens it', function () {
        [$dev, $devProfile] = createDevUserWithProfile();
        $company = createUserWithRole('company');

        asChatUser($company);

        $response = postJson('/api/chat', ['user_id' => $dev->id]);

        $response
            ->assertOk()
            ->assertJsonPath('data.participant.id', $dev->id)
            ->assertJsonPath('data.participant.role', 'dev')
            ->assertJsonPath('data.participant.profile_id', $devProfile->id)
            ->assertJsonPath('data.participant.name', $devProfile->name)
            ->assertJsonPath('data.last_message', null)
            ->assertJsonPath('data.unread_count', 0);

        asChatUser($dev);

        postJson('/api/chat', ['user_id' => $company->id])
            ->assertOk()
            ->assertJsonPath('data.id', $response->json('data.id'))
            ->assertJsonPath('data.participant.id', $company->id)
            ->assertJsonPath('data.participant.role', 'company')
            ->assertJsonPath('data.participant.name', null);

        expect(ChatConversation::count())->toBe(1);
    });

    it('does not allow a conversation with yourself', function () {
        $client = createUserWithRole('client');

        asChatUser($client);

        postJson('/api/chat', ['user_id' => $client->id])
            ->assertStatus(400)
            ->assertJson(['error' => true]);

        expect(ChatConversation::count())->toBe(0);
    });
});

describe('POST /api/chat/{id}/messages', function () {
    it('stores the message and broadcasts it to both participants', function () {
        Event::fake([ChatMessageSent::class]);

        $dev = createUserWithRole('dev');
        $client = createUserWithRole('client');

        $conversationId = openChatConversation($dev, $client);

        $response = postJson("/api/chat/{$conversationId}/messages", ['body' => 'Oi, tudo bem?']);

        $response
            ->assertCreated()
            ->assertJsonPath('data.conversation_id', $conversationId)
            ->assertJsonPath('data.sender_id', $dev->id)
            ->assertJsonPath('data.body', 'Oi, tudo bem?')
            ->assertJsonPath('data.read_at', null);

        Event::assertDispatched(ChatMessageSent::class, function (ChatMessageSent $event) use ($dev, $client, $response) {
            return $event->message->id === $response->json('data.id')
                && broadcastChannelNames($event) === ["private-chat.{$client->id}", "private-chat.{$dev->id}"]
                && $event->broadcastAs() === 'message.sent';
        });

        expect(ChatConversation::find($conversationId)->last_message_at)->not->toBeNull();
    });

    it('delivers the message and the read through the broadcaster without errors', function () {
        Exceptions::fake();

        $dev = createUserWithRole('dev');
        $company = createUserWithRole('company');

        $conversationId = openChatConversation($dev, $company);
        sendChatMessage($dev, $conversationId);

        asChatUser($company);

        patchJson("/api/chat/{$conversationId}/read")->assertOk();

        Exceptions::assertNothingReported();
    });

    it('denies access to a conversation of other users', function () {
        $dev = createUserWithRole('dev');
        $company = createUserWithRole('company');
        $intruder = createUserWithRole('client');

        $conversationId = openChatConversation($dev, $company);

        asChatUser($intruder);

        postJson("/api/chat/{$conversationId}/messages", ['body' => 'Olá'])->assertForbidden();
        getJson("/api/chat/{$conversationId}/messages")->assertForbidden();
        getJson("/api/chat/{$conversationId}")->assertForbidden();
        patchJson("/api/chat/{$conversationId}/read")->assertForbidden();

        expect(ChatMessage::count())->toBe(0);
    });

    it('rejects an empty message', function () {
        $dev = createUserWithRole('dev');
        $company = createUserWithRole('company');

        $conversationId = openChatConversation($dev, $company);

        postJson("/api/chat/{$conversationId}/messages", ['body' => '   '])
            ->assertUnprocessable()
            ->assertJsonStructure(['data' => ['body']]);
    });
});

describe('GET /api/chat', function () {
    it('lists only conversations with messages, with the last message and the unread count of each', function () {
        $dev = createUserWithRole('dev');
        $company = createUserWithRole('company');
        $client = createUserWithRole('client');
        $admin = createUserWithRole('admin');

        $withCompany = openChatConversation($dev, $company);
        $withClient = openChatConversation($dev, $client);
        openChatConversation($dev, $admin);

        sendChatMessage($company, $withCompany, 'Empresa 1');
        $this->travel(1)->seconds();
        sendChatMessage($company, $withCompany, 'Empresa 2');
        $this->travel(1)->seconds();
        sendChatMessage($client, $withClient, 'Cliente 1');
        $this->travel(1)->seconds();
        sendChatMessage($dev, $withCompany, 'Dev para empresa');

        asChatUser($dev);

        getJson('/api/chat')
            ->assertOk()
            ->assertJsonCount(2, 'data.data')
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.data.0.id', $withCompany)
            ->assertJsonPath('data.data.0.participant.id', $company->id)
            ->assertJsonPath('data.data.0.last_message.body', 'Dev para empresa')
            ->assertJsonPath('data.data.0.unread_count', 2)
            ->assertJsonPath('data.data.1.id', $withClient)
            ->assertJsonPath('data.data.1.participant.id', $client->id)
            ->assertJsonPath('data.data.1.last_message.body', 'Cliente 1')
            ->assertJsonPath('data.data.1.unread_count', 1);
    });
});

describe('GET /api/chat/{id}/messages', function () {
    it('lists the messages from the newest to the oldest', function () {
        $dev = createUserWithRole('dev');
        $company = createUserWithRole('company');

        $conversationId = openChatConversation($dev, $company);

        foreach (['Primeira', 'Segunda', 'Terceira'] as $body) {
            sendChatMessage($dev, $conversationId, $body);
            $this->travel(1)->seconds();
        }

        asChatUser($company);

        getJson("/api/chat/{$conversationId}/messages?per_page=2")
            ->assertOk()
            ->assertJsonPath('data.data.0.body', 'Terceira')
            ->assertJsonPath('data.data.1.body', 'Segunda')
            ->assertJsonPath('data.pagination.total', 3)
            ->assertJsonPath('data.pagination.total_pages', 2);
    });
});

describe('PATCH /api/chat/{id}/read', function () {
    it('marks only the received messages as read and notifies the sender', function () {
        Event::fake([ChatMessagesRead::class]);

        $dev = createUserWithRole('dev');
        $company = createUserWithRole('company');

        $conversationId = openChatConversation($dev, $company);
        sendChatMessage($dev, $conversationId, 'Olá');
        sendChatMessage($dev, $conversationId, 'Tudo bem?');
        $ownMessageId = sendChatMessage($company, $conversationId, 'Tudo e você?');

        asChatUser($company);

        patchJson("/api/chat/{$conversationId}/read")
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        Event::assertDispatched(ChatMessagesRead::class, function (ChatMessagesRead $event) use ($dev, $company, $conversationId) {
            return $event->conversationId === $conversationId
                && $event->readerId === $company->id
                && broadcastChannelNames($event) === ["private-chat.{$company->id}", "private-chat.{$dev->id}"]
                && $event->broadcastAs() === 'messages.read';
        });

        expect(ChatMessage::where('sender_id', $dev->id)->whereNull('read_at')->count())->toBe(0)
            ->and(ChatMessage::find($ownMessageId)->read_at)->toBeNull();

        asChatUser($dev);

        $readAts = collect(getJson("/api/chat/{$conversationId}/messages")->json('data.data'))
            ->where('sender_id', $dev->id)
            ->pluck('read_at');

        expect($readAts)->toHaveCount(2)
            ->and($readAts->filter())->toHaveCount(2);
    });

    it('does not broadcast anything when there is nothing new to read', function () {
        Event::fake([ChatMessagesRead::class]);

        $dev = createUserWithRole('dev');
        $company = createUserWithRole('company');

        $conversationId = openChatConversation($dev, $company);

        patchJson("/api/chat/{$conversationId}/read")->assertOk();

        Event::assertNotDispatched(ChatMessagesRead::class);
    });

    it('hides the read from the sender when the reader disabled the read receipts', function () {
        Event::fake([ChatMessagesRead::class]);

        $dev = createUserWithRole('dev');
        $company = createUserWithRole('company');
        $company->update(['read_receipts_enabled' => false]);

        $conversationId = openChatConversation($dev, $company);
        sendChatMessage($dev, $conversationId, 'Olá');

        asChatUser($company);

        patchJson("/api/chat/{$conversationId}/read")
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        // Para quem leu, a mensagem continua lida
        expect(getJson("/api/chat/{$conversationId}/messages")->json('data.data.0.read_at'))->not->toBeNull();

        // Só o canal de quem leu recebe o evento
        Event::assertDispatched(ChatMessagesRead::class, fn (ChatMessagesRead $event) =>
            broadcastChannelNames($event) === ["private-chat.{$company->id}"]
        );

        asChatUser($dev);

        getJson("/api/chat/{$conversationId}/messages")->assertJsonPath('data.data.0.read_at', null);
        getJson('/api/chat')->assertJsonPath('data.data.0.last_message.read_at', null);
    });

    it('hides the reads of others from a sender who disabled his own read receipts', function () {
        Event::fake([ChatMessagesRead::class]);

        $dev = createUserWithRole('dev');
        $company = createUserWithRole('company');
        $dev->update(['read_receipts_enabled' => false]);

        $conversationId = openChatConversation($dev, $company);
        sendChatMessage($dev, $conversationId, 'Olá');

        asChatUser($company);

        patchJson("/api/chat/{$conversationId}/read")->assertOk();

        Event::assertDispatched(ChatMessagesRead::class, fn (ChatMessagesRead $event) =>
            broadcastChannelNames($event) === ["private-chat.{$company->id}"]
        );

        asChatUser($dev);

        getJson("/api/chat/{$conversationId}/messages")->assertJsonPath('data.data.0.read_at', null);

        // Religando, ele volta a ver a leitura, que foi feita com a confirmação liberada
        $dev->update(['read_receipts_enabled' => true]);

        expect(getJson("/api/chat/{$conversationId}/messages")->json('data.data.0.read_at'))->not->toBeNull();
    });

    it('confirms the reads made with the receipts disabled, with the new read time, once the reader reads again with them enabled', function () {
        $dev = createUserWithRole('dev');
        $company = createUserWithRole('company');
        $company->update(['read_receipts_enabled' => false]);

        $conversationId = openChatConversation($dev, $company);
        sendChatMessage($dev, $conversationId, 'Olá');

        asChatUser($company);

        patchJson("/api/chat/{$conversationId}/read")->assertOk();

        // Religar sozinho não revela nada
        $company->update(['read_receipts_enabled' => true]);

        asChatUser($dev);

        getJson("/api/chat/{$conversationId}/messages")->assertJsonPath('data.data.0.read_at', null);

        $this->travel(5)->minutes();

        asChatUser($company);

        patchJson("/api/chat/{$conversationId}/read")->assertOk();

        asChatUser($dev);

        $readAt = getJson("/api/chat/{$conversationId}/messages")->json('data.data.0.read_at');

        expect($readAt)->not->toBeNull()
            ->and(Carbon::parse($readAt)->equalTo(now()->startOfSecond()))->toBeTrue();
    });
});

describe('PATCH /api/user/{id}', function () {
    it('allows the user to disable the chat read receipts', function () {
        $dev = createUserWithRole('dev');

        asChatUser($dev);

        patchJson("/api/user/{$dev->id}", ['read_receipts_enabled' => false])
            ->assertOk()
            ->assertJsonPath('data.read_receipts_enabled', false);

        expect($dev->fresh()->read_receipts_enabled)->toBeFalse();
    });
});
