<?php

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    seedRolesAndPermissions();
});

function createPost(User $user, string $content = 'Meu primeiro post'): Post
{
    return Post::create(['user_id' => $user->id, 'content' => $content]);
}

function createComment(Post $post, User $user, ?Comment $parent = null, string $content = 'comentário'): Comment
{
    return Comment::create([
        'user_id' => $user->id,
        'post_id' => $post->id,
        'parent_id' => $parent?->id,
        'content' => $content,
    ]);
}

describe('posts', function () {
    it('lets any profile publish a post', function (string $role) {
        $user = createUserWithRole($role);

        postJson('/api/post', ['content' => 'Olá rede!'], actingAsApi($user))
            ->assertCreated()
            ->assertJsonPath('data.content', 'Olá rede!')
            ->assertJsonPath('data.author.id', $user->id)
            ->assertJsonPath('data.likes_count', 0)
            ->assertJsonPath('data.is_liked', false);
    })->with(['dev', 'company', 'client']);

    it('shows the author name from the profile', function () {
        [$user, $profile] = createDevUserWithProfile();

        postJson('/api/post', ['content' => 'Post com perfil'], actingAsApi($user))
            ->assertCreated()
            ->assertJsonPath('data.author.name', $profile->name)
            ->assertJsonPath('data.author.profile_id', $profile->id)
            ->assertJsonPath('data.author.profile_type', 'dev');
    });

    it('lists the feed newest first with counts and the viewer state', function () {
        $author = createUserWithRole('dev');
        $viewer = createUserWithRole('company');

        $older = createPost($author, 'antigo');
        $this->travel(1)->minutes();
        $newer = createPost($author, 'novo');

        $newer->toggleLike($viewer->id);
        createComment($newer, $viewer);

        getJson('/api/post', actingAsApi($viewer))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.data.0.id', $newer->id)
            ->assertJsonPath('data.data.0.likes_count', 1)
            ->assertJsonPath('data.data.0.comments_count', 1)
            ->assertJsonPath('data.data.0.is_liked', true)
            ->assertJsonPath('data.data.1.id', $older->id)
            ->assertJsonPath('data.data.1.is_liked', false);
    });

    it('only lets the author edit a post', function () {
        $author = createUserWithRole('dev');
        $other = createUserWithRole('dev');
        $post = createPost($author);

        patchJson("/api/post/{$post->id}", ['content' => 'invadido'], actingAsApi($other))
            ->assertForbidden();

        patchJson("/api/post/{$post->id}", ['content' => 'editado'], actingAsApi($author))
            ->assertOk()
            ->assertJsonPath('data.content', 'editado');
    });

    it('lets the author or an admin delete a post', function () {
        $author = createUserWithRole('dev');
        $other = createUserWithRole('client');
        $admin = createUserWithRole('admin');
        $post = createPost($author);

        deleteJson("/api/post/{$post->id}", [], actingAsApi($other))->assertForbidden();
        deleteJson("/api/post/{$post->id}", [], actingAsApi($admin))->assertOk();

        expect(Post::find($post->id))->toBeNull();
        getJson("/api/post/{$post->id}", actingAsApi($author))->assertUnprocessable();
    });

    it('toggles favorites and lists them', function () {
        $user = createUserWithRole('dev');
        $post = createPost(createUserWithRole('company'));
        createPost($user, 'não favoritado');

        postJson("/api/post/{$post->id}/favorite", [], actingAsApi($user))
            ->assertOk()
            ->assertJsonPath('data.favorited', true)
            ->assertJsonPath('data.favorites_count', 1);

        getJson('/api/post/favorites', actingAsApi($user))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.data.0.id', $post->id)
            ->assertJsonPath('data.data.0.is_favorited', true);

        postJson("/api/post/{$post->id}/favorite", [], actingAsApi($user))
            ->assertOk()
            ->assertJsonPath('data.favorited', false)
            ->assertJsonPath('data.favorites_count', 0);
    });
});

describe('comments', function () {
    it('creates comments and replies on the same post only', function () {
        $user = createUserWithRole('dev');
        $post = createPost($user);
        $otherPost = createPost($user);
        $root = createComment($post, $user);

        postJson('/api/comment', ['post_id' => $post->id, 'parent_id' => $root->id, 'content' => 'resposta'], actingAsApi($user))
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $root->id)
            ->assertJsonPath('data.replies_count', 0);

        postJson('/api/comment', ['post_id' => $otherPost->id, 'parent_id' => $root->id, 'content' => 'resposta'], actingAsApi($user))
            ->assertUnprocessable();
    });

    it('returns a preview of the reply tree limited in breadth and depth', function () {
        $user = createUserWithRole('dev');
        $post = createPost($user);
        $root = createComment($post, $user, content: 'root');

        $replies = collect(range(1, 5))->map(function ($i) use ($post, $user, $root) {
            $this->travel(1)->seconds();

            return createComment($post, $user, $root, "l1-{$i}");
        });

        // Cadeia mais funda que MAX_DEPTH a partir da primeira resposta.
        $node = $replies->first();
        foreach (['l2', 'l3', 'l4'] as $label) {
            $node = createComment($post, $user, $node, $label);
        }

        $response = getJson("/api/comment?post_id={$post->id}", actingAsApi($user))->assertOk();

        $rootJson = $response->json('data.data.0');
        expect($rootJson['replies_count'])->toBe(5)
            ->and($rootJson['replies'])->toHaveCount(3)
            ->and($rootJson['has_more_replies'])->toBeTrue();

        $level1 = $rootJson['replies'][0];
        $level2 = $level1['replies'][0];
        $level3 = $level2['replies'][0];

        expect($level1['content'])->toBe('l1-1')
            ->and($level3['content'])->toBe('l3')
            ->and($level3['replies'])->toBe([])
            ->and($level3['replies_count'])->toBe(1)
            ->and($level3['has_more_replies'])->toBeTrue();

        getJson("/api/comment/{$root->id}/replies?per_page=2&page=3", actingAsApi($user))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 5)
            ->assertJsonPath('data.data.0.content', 'l1-5');
    });

    it('removes a comment logically keeping its replies', function () {
        $author = createUserWithRole('dev');
        $other = createUserWithRole('dev');
        $post = createPost($author);
        $root = createComment($post, $author, content: 'original');
        createComment($post, $other, $root);

        deleteJson("/api/comment/{$root->id}", [], actingAsApi($other))->assertForbidden();
        deleteJson("/api/comment/{$root->id}", [], actingAsApi($author))->assertOk();

        getJson("/api/comment/{$root->id}", actingAsApi($author))
            ->assertOk()
            ->assertJsonPath('data.is_removed', true)
            ->assertJsonPath('data.content', Comment::REMOVED_CONTENT)
            ->assertJsonPath('data.replies_count', 1);

        expect($root->fresh()->original_content)->toBe('original');

        patchJson("/api/comment/{$root->id}", ['content' => 'volta'], actingAsApi($author))
            ->assertUnprocessable();
    });

    it('blocks replies and likes on a removed comment and hides its author', function () {
        $user = createUserWithRole('dev');
        $post = createPost($user);
        $root = createComment($post, $user);

        deleteJson("/api/comment/{$root->id}", [], actingAsApi($user))->assertOk();

        postJson('/api/comment', ['post_id' => $post->id, 'parent_id' => $root->id, 'content' => 'resposta'], actingAsApi($user))
            ->assertUnprocessable();

        postJson("/api/like/comment/{$root->id}/toggle", [], actingAsApi($user))
            ->assertUnprocessable();

        getJson("/api/comment/{$root->id}", actingAsApi($user))
            ->assertOk()
            ->assertJsonPath('data.author', null);
    });

    it('blocks access to the comments of a deleted post', function () {
        $user = createUserWithRole('dev');
        $post = createPost($user);
        $comment = createComment($post, $user);

        $post->delete();

        getJson("/api/comment/{$comment->id}", actingAsApi($user))->assertUnprocessable();
        getJson("/api/comment/{$comment->id}/replies", actingAsApi($user))->assertUnprocessable();
        patchJson("/api/comment/{$comment->id}", ['content' => 'editado'], actingAsApi($user))->assertUnprocessable();
        deleteJson("/api/comment/{$comment->id}", [], actingAsApi($user))->assertUnprocessable();
        postJson("/api/like/comment/{$comment->id}/toggle", [], actingAsApi($user))->assertUnprocessable();
        getJson("/api/like/comment/{$comment->id}", actingAsApi($user))->assertUnprocessable();
    });

    it('keeps the comments of a deleted account and the replies to them', function () {
        $author = createUserWithRole('dev');
        $other = createUserWithRole('dev');
        $post = createPost($other);
        $root = createComment($post, $author);
        $reply = createComment($post, $other, $root);

        $author->delete();

        expect($reply->fresh())->not->toBeNull();

        getJson("/api/comment/{$root->id}", actingAsApi($other))
            ->assertOk()
            ->assertJsonPath('data.author', null)
            ->assertJsonPath('data.replies_count', 1);
    });
});

describe('likes', function () {
    it('toggles likes on posts and comments', function () {
        $user = createUserWithRole('client');
        $post = createPost(createUserWithRole('dev'));
        $comment = createComment($post, $user);

        postJson("/api/like/post/{$post->id}/toggle", [], actingAsApi($user))
            ->assertOk()
            ->assertJsonPath('data.liked', true)
            ->assertJsonPath('data.likes_count', 1);

        postJson("/api/like/post/{$post->id}/toggle", [], actingAsApi($user))
            ->assertOk()
            ->assertJsonPath('data.liked', false)
            ->assertJsonPath('data.likes_count', 0);

        postJson("/api/like/comment/{$comment->id}/toggle", [], actingAsApi($user))
            ->assertOk()
            ->assertJsonPath('data.liked', true);

        getJson("/api/comment?post_id={$post->id}", actingAsApi($user))
            ->assertJsonPath('data.data.0.likes_count', 1)
            ->assertJsonPath('data.data.0.is_liked', true);

        getJson("/api/like/comment/{$comment->id}", actingAsApi($user))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.data.0.user.id', $user->id);
    });
});
