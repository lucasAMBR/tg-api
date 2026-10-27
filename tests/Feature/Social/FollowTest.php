<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    seedRolesAndPermissions();
});

function followUser(User $follower, User $followed): void
{
    $followed->followers()->attach($follower->id);
}

describe('toggle', function () {
    it('follows and unfollows a user without following back', function () {
        $follower = createUserWithRole('dev');
        $followed = createUserWithRole('company');

        postJson("/api/follow/{$followed->id}/toggle", [], actingAsApi($follower))
            ->assertOk()
            ->assertJsonPath('data.following', true)
            ->assertJsonPath('data.followers_count', 1);

        expect($follower->following()->whereKey($followed->id)->exists())->toBeTrue()
            ->and($followed->following()->count())->toBe(0);

        postJson("/api/follow/{$followed->id}/toggle", [], actingAsApi($follower))
            ->assertOk()
            ->assertJsonPath('data.following', false)
            ->assertJsonPath('data.followers_count', 0);
    });

    it('lets any profile follow', function (string $role) {
        $followed = createUserWithRole('dev');

        postJson("/api/follow/{$followed->id}/toggle", [], actingAsApi(createUserWithRole($role)))
            ->assertOk()
            ->assertJsonPath('data.following', true);
    })->with(['dev', 'company', 'client', 'admin']);

    it('does not let a user follow themselves', function () {
        $user = createUserWithRole('dev');

        postJson("/api/follow/{$user->id}/toggle", [], actingAsApi($user))
            ->assertUnprocessable();

        expect($user->following()->count())->toBe(0);
    });

    it('rejects an unknown user', function () {
        postJson('/api/follow/' . Str::uuid() . '/toggle', [], actingAsApi(createUserWithRole('dev')))
            ->assertUnprocessable();
    });
});

describe('lists', function () {
    it('lists followers newest first with the viewer relationship', function () {
        $user = createUserWithRole('dev');
        $older = createUserWithRole('company');
        $newer = createUserWithRole('client');
        $viewer = createUserWithRole('dev');

        followUser($older, $user);
        $this->travel(1)->minutes();
        followUser($newer, $user);

        followUser($viewer, $newer);
        followUser($older, $viewer);

        getJson("/api/follow/{$user->id}/followers", actingAsApi($viewer))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.data.0.user.id', $newer->id)
            ->assertJsonPath('data.data.0.is_followed', true)
            ->assertJsonPath('data.data.0.follows_you', false)
            ->assertJsonPath('data.data.1.user.id', $older->id)
            ->assertJsonPath('data.data.1.is_followed', false)
            ->assertJsonPath('data.data.1.follows_you', true);
    });

    it('lists only who the user follows', function () {
        $user = createUserWithRole('dev');
        $followed = createUserWithRole('company');

        followUser($user, $followed);
        followUser(createUserWithRole('client'), $user);

        getJson("/api/follow/{$user->id}/following", actingAsApi($user))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.data.0.user.id', $followed->id)
            ->assertJsonPath('data.data.0.is_followed', true);
    });
});

describe('profile', function () {
    it('shows the follow counts and the viewer relationship on the profile', function () {
        [$user, $profile] = createDevUserWithProfile();
        $viewer = createUserWithRole('company');

        followUser($viewer, $user);
        followUser(createUserWithRole('client'), $user);
        followUser($user, $viewer);

        getJson("/api/profile/dev/{$profile->id}", actingAsApi($viewer))
            ->assertOk()
            ->assertJsonPath('data.user.followers_count', 2)
            ->assertJsonPath('data.user.following_count', 1)
            ->assertJsonPath('data.user.is_followed', true)
            ->assertJsonPath('data.user.follows_you', true);
    });

    it('shows the own counts on the authenticated profile', function () {
        $user = createUserWithRole('dev');

        followUser(createUserWithRole('company'), $user);

        getJson('/api/auth/profile', actingAsApi($user))
            ->assertOk()
            ->assertJsonPath('data.user.followers_count', 1)
            ->assertJsonPath('data.user.following_count', 0);
    });
});

describe('following feed', function () {
    it('shows only posts from followed users and the own ones', function () {
        $viewer = createUserWithRole('dev');
        $followed = createUserWithRole('company');
        $stranger = createUserWithRole('client');

        followUser($viewer, $followed);
        // Ser seguido por alguém não coloca os posts dessa pessoa no feed.
        followUser($stranger, $viewer);

        $own = Post::create(['user_id' => $viewer->id, 'content' => 'meu']);
        $this->travel(1)->minutes();
        $fromFollowed = Post::create(['user_id' => $followed->id, 'content' => 'seguido']);
        Post::create(['user_id' => $stranger->id, 'content' => 'desconhecido']);

        getJson('/api/post?feed=following', actingAsApi($viewer))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.data.0.id', $fromFollowed->id)
            ->assertJsonPath('data.data.1.id', $own->id);
    });

    it('orders the following feed by popularity', function () {
        $viewer = createUserWithRole('dev');
        $followed = createUserWithRole('company');
        $stranger = createUserWithRole('client');

        followUser($viewer, $followed);

        $popular = Post::create(['user_id' => $followed->id, 'content' => 'popular']);
        $this->travel(1)->minutes();
        $recent = Post::create(['user_id' => $followed->id, 'content' => 'recente']);
        $viral = Post::create(['user_id' => $stranger->id, 'content' => 'viral de quem não é seguido']);

        $popular->toggleLike($stranger->id);
        $viral->toggleLike($viewer->id);
        $viral->toggleLike($followed->id);

        getJson('/api/post?feed=following&sort=popular', actingAsApi($viewer))
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.data.0.id', $popular->id)
            ->assertJsonPath('data.data.1.id', $recent->id);
    });

    it('rejects an unknown feed', function () {
        getJson('/api/post?feed=amigos', actingAsApi(createUserWithRole('dev')))
            ->assertUnprocessable();
    });
});
