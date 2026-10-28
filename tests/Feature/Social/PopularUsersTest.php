<?php

use App\Models\Comment;
use App\Models\CompanyProfile;
use App\Models\DevProfile;
use App\Models\Post;
use App\Models\User;
use function Pest\Laravel\getJson;

beforeEach(function () {
    seedRolesAndPermissions();
});

function createRankedDev(): User
{
    $user = createUserWithRole('dev');

    DevProfile::factory()->create(['user_id' => $user->id]);

    return $user;
}

it('ranks users by the engagement their posts received plus their followers', function () {
    $author = createRankedDev();
    $followed = createRankedDev();
    $quiet = createRankedDev();
    $viewer = createRankedDev();

    // Autor: 2 curtidas + 1 comentário + 1 favorito + 1 seguidor = 5.
    $post = Post::create(['user_id' => $author->id, 'content' => 'post popular']);
    $post->toggleLike($viewer->id);
    $post->toggleLike($quiet->id);
    Comment::create(['user_id' => $viewer->id, 'post_id' => $post->id, 'content' => 'boa!']);
    $post->favoritedByUsers()->attach($viewer->id);
    $author->followers()->attach($viewer->id);

    // Só seguidores: 3.
    $followed->followers()->attach([$viewer->id, $quiet->id, $author->id]);

    getJson('/api/user/popular', actingAsApi($viewer))
        ->assertOk()
        ->assertJsonPath('data.pagination.total', 4)
        ->assertJsonPath('data.data.0.user.id', $author->id)
        ->assertJsonPath('data.data.0.popularity_score', 5)
        ->assertJsonPath('data.data.0.received_likes_count', 2)
        ->assertJsonPath('data.data.0.received_comments_count', 1)
        ->assertJsonPath('data.data.0.received_favorites_count', 1)
        ->assertJsonPath('data.data.0.followers_count', 1)
        ->assertJsonPath('data.data.0.is_followed', true)
        ->assertJsonPath('data.data.0.follows_you', false)
        ->assertJsonPath('data.data.1.user.id', $followed->id)
        ->assertJsonPath('data.data.1.popularity_score', 3)
        ->assertJsonPath('data.data.1.followers_count', 3);
});

it('counts replies but not the author own engagement, removed comments or deleted posts', function () {
    $author = createRankedDev();
    $fan = createRankedDev();

    $post = Post::create(['user_id' => $author->id, 'content' => 'post']);
    $comment = Comment::create(['user_id' => $fan->id, 'post_id' => $post->id, 'content' => 'pergunta']);
    Comment::create(['user_id' => $fan->id, 'post_id' => $post->id, 'parent_id' => $comment->id, 'content' => 'complemento']);

    // Nada disto conta.
    $post->toggleLike($author->id);
    $post->favoritedByUsers()->attach($author->id);
    Comment::create(['user_id' => $author->id, 'post_id' => $post->id, 'parent_id' => $comment->id, 'content' => 'resposta do autor']);
    Comment::create([
        'user_id' => $fan->id,
        'post_id' => $post->id,
        'content' => Comment::REMOVED_CONTENT,
        'original_content' => 'apagado',
        'removed_at' => now(),
    ]);

    $deleted = Post::create(['user_id' => $author->id, 'content' => 'excluído']);
    $deleted->toggleLike($fan->id);
    $deleted->delete();

    $ranking = collect(getJson('/api/user/popular', actingAsApi($fan))->assertOk()->json('data.data'))
        ->keyBy('user.id');

    expect($ranking[$author->id]['received_comments_count'])->toBe(2)
        ->and($ranking[$author->id]['received_likes_count'])->toBe(0)
        ->and($ranking[$author->id]['received_favorites_count'])->toBe(0)
        ->and($ranking[$author->id]['popularity_score'])->toBe(2);
});

it('leaves out blocked users and users without profile', function () {
    $viewer = createRankedDev();

    $blocked = createRankedDev();
    $blocked->update(['is_blocked' => true]);
    $blocked->followers()->attach($viewer->id);

    $withoutProfile = createUserWithRole('dev');
    $withoutProfile->followers()->attach($viewer->id);

    getJson('/api/user/popular', actingAsApi($viewer))
        ->assertOk()
        ->assertJsonPath('data.pagination.total', 1)
        ->assertJsonPath('data.data.0.user.id', $viewer->id);
});

it('filters the ranking by profile type', function () {
    $dev = createRankedDev();
    $company = createUserWithRole('company');

    CompanyProfile::create([
        'user_id' => $company->id,
        'name' => 'ACME Tecnologia',
        'bio' => 'Empresa de testes.',
        'cnpj' => '11222333000181',
        'phone' => '+5511999990000',
        'founding_date' => '2015-03-10',
        'operational_segment' => 'fintech',
    ]);

    getJson('/api/user/popular?profile_type=company', actingAsApi($dev))
        ->assertOk()
        ->assertJsonPath('data.pagination.total', 1)
        ->assertJsonPath('data.data.0.user.id', $company->id)
        ->assertJsonPath('data.data.0.user.profile_type', 'company');

    getJson('/api/user/popular?profile_type=admin', actingAsApi($dev))
        ->assertUnprocessable();
});
