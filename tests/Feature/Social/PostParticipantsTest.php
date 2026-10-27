<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;
use function Pest\Laravel\getJson;

beforeEach(function () {
    seedRolesAndPermissions();
});

function joinConversation(Post $post, ?User $user, ?Comment $parent = null, array $attributes = []): Comment
{
    return Comment::create(array_merge([
        'user_id' => $user?->id,
        'post_id' => $post->id,
        'parent_id' => $parent?->id,
        'content' => 'comentário',
    ], $attributes));
}

function removedCommentAttributes(): array
{
    return [
        'content' => Comment::REMOVED_CONTENT,
        'original_content' => 'apagado',
        'removed_at' => now(),
    ];
}

it('lists everyone in the comments conversation once, most active first', function () {
    $author = createUserWithRole('dev');
    $talkative = createUserWithRole('company');
    $quiet = createUserWithRole('client');
    $viewer = createUserWithRole('dev');

    $post = Post::create(['user_id' => $author->id, 'content' => 'post']);

    $root = joinConversation($post, $talkative);
    $this->travel(1)->minutes();
    joinConversation($post, $author, $root);
    $this->travel(1)->minutes();
    $lastReply = joinConversation($post, $talkative, $root);
    $this->travel(1)->minutes();
    joinConversation($post, $quiet);

    // Comentário em outra postagem não entra nesta conversa.
    $other = Post::create(['user_id' => $author->id, 'content' => 'outro post']);
    joinConversation($other, $viewer);

    $viewer->following()->attach($talkative->id);
    $talkative->following()->attach($viewer->id);

    getJson("/api/post/{$post->id}/participants", actingAsApi($viewer))
        ->assertOk()
        ->assertJsonPath('data.pagination.total', 3)
        ->assertJsonPath('data.data.0.user.id', $talkative->id)
        ->assertJsonPath('data.data.0.comments_count', 2)
        ->assertJsonPath('data.data.0.is_post_author', false)
        ->assertJsonPath('data.data.0.first_commented_at', $root->created_at->toIso8601String())
        ->assertJsonPath('data.data.0.last_commented_at', $lastReply->created_at->toIso8601String())
        ->assertJsonPath('data.data.0.is_followed', true)
        ->assertJsonPath('data.data.0.follows_you', true)
        // Empatados em um comentário, quem participou por último vem antes.
        ->assertJsonPath('data.data.1.user.id', $quiet->id)
        ->assertJsonPath('data.data.1.is_followed', false)
        ->assertJsonPath('data.data.2.user.id', $author->id)
        ->assertJsonPath('data.data.2.comments_count', 1)
        ->assertJsonPath('data.data.2.is_post_author', true);
});

it('ignores removed comments and comments from deleted accounts', function () {
    $author = createUserWithRole('dev');
    $remover = createUserWithRole('dev');
    $participant = createUserWithRole('company');

    $post = Post::create(['user_id' => $author->id, 'content' => 'post']);

    // Removido não expõe mais o autor na árvore, então não pode aparecer aqui.
    joinConversation($post, $remover, attributes: removedCommentAttributes());
    joinConversation($post, $participant);
    joinConversation($post, $participant, attributes: removedCommentAttributes());
    joinConversation($post, null, attributes: ['content' => 'de uma conta excluída']);

    getJson("/api/post/{$post->id}/participants", actingAsApi($author))
        ->assertOk()
        ->assertJsonPath('data.pagination.total', 1)
        ->assertJsonPath('data.data.0.user.id', $participant->id)
        ->assertJsonPath('data.data.0.comments_count', 1);
});

it('does not list the post author when they did not comment', function () {
    $author = createUserWithRole('dev');

    $post = Post::create(['user_id' => $author->id, 'content' => 'post sem conversa']);

    getJson("/api/post/{$post->id}/participants", actingAsApi($author))
        ->assertOk()
        ->assertJsonPath('data.pagination.total', 0)
        ->assertJsonCount(0, 'data.data');
});

it('paginates people, not comments', function () {
    $author = createUserWithRole('dev');
    $post = Post::create(['user_id' => $author->id, 'content' => 'post']);

    $regular = createUserWithRole('dev');
    joinConversation($post, $regular);
    joinConversation($post, $regular);
    joinConversation($post, $regular);
    joinConversation($post, createUserWithRole('company'));
    joinConversation($post, createUserWithRole('client'));

    getJson("/api/post/{$post->id}/participants?per_page=2", actingAsApi($author))
        ->assertOk()
        ->assertJsonPath('data.pagination.total', 3)
        ->assertJsonPath('data.pagination.total_pages', 2)
        ->assertJsonCount(2, 'data.data')
        ->assertJsonPath('data.data.0.user.id', $regular->id);

    getJson("/api/post/{$post->id}/participants?per_page=2&page=2", actingAsApi($author))
        ->assertOk()
        ->assertJsonCount(1, 'data.data');
});

it('rejects a deleted or unknown post', function () {
    $user = createUserWithRole('dev');

    $post = Post::create(['user_id' => $user->id, 'content' => 'excluído']);
    joinConversation($post, $user);
    $post->delete();

    getJson("/api/post/{$post->id}/participants", actingAsApi($user))
        ->assertUnprocessable();

    getJson('/api/post/' . Str::uuid() . '/participants', actingAsApi($user))
        ->assertUnprocessable();
});
