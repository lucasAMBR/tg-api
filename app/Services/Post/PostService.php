<?php

namespace App\Services\Post;

use App\Exceptions\ApiException;
use App\Http\Resources\Post\PostCollection;
use App\Http\Resources\Post\PostResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PostService
{
    private const MAX_IMAGES = 10;

    public function index(array $data): PostCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $userId = $data['user_id'] ?? null;

        $posts = $this->query()
            ->when($userId, function (Builder $query, $userId) {
                $query->where('user_id', $userId);
            })
            ->when($search, function (Builder $query, $search) {
                $query->where('content', 'ILIKE', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return new PostCollection($posts);
    }

    public function indexFavorites(array $data): PostCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;

        $posts = $this->query()
            ->whereHas('favoritedByUsers', function (Builder $query) {
                $query->where('users.id', Auth::id());
            })
            ->when($search, function (Builder $query, $search) {
                $query->where('content', 'ILIKE', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return new PostCollection($posts);
    }

    public function show(array $data): PostResource
    {
        return new PostResource($this->query()->findOrFail($data['id']));
    }

    public function store(array $data): PostResource
    {
        $post = DB::transaction(function () use ($data) {
            $post = Post::create([
                'user_id' => Auth::id(),
                'content' => $data['content'],
            ]);

            foreach ($data['images'] ?? [] as $image) {
                $post->addMedia($image)->toMediaCollection('images');
            }

            return $post;
        });

        return new PostResource($this->query()->find($post->id));
    }

    public function update(array $data): PostResource
    {
        $post = Post::findOrFail($data['id']);

        $this->ensureAbility('update', $post);

        $post->update(['content' => $data['content']]);

        return new PostResource($this->query()->find($post->id));
    }

    public function delete(array $data): void
    {
        $post = Post::findOrFail($data['id']);

        $this->ensureAbility('delete', $post);

        $post->delete();
    }

    public function saveImages(array $data): PostResource
    {
        $post = Post::findOrFail($data['id']);

        $this->ensureAbility('update', $post);

        if ($post->getMedia('images')->count() + count($data['images']) > self::MAX_IMAGES) {
            throw new ApiException('A post can have at most ' . self::MAX_IMAGES . ' images!', 422);
        }

        foreach ($data['images'] as $image) {
            $post->addMedia($image)->toMediaCollection('images');
        }

        return new PostResource($this->query()->find($post->id));
    }

    public function removeImage(array $data): void
    {
        $post = Post::findOrFail($data['id']);

        $this->ensureAbility('update', $post);

        $media = $post->getMedia('images')->firstWhere('id', (int) $data['image_id']);

        if (!$media) {
            throw new ApiException("This image doesn't belong to this post!", 404);
        }

        $media->delete();
    }

    /**
     * @return array{favorited: bool, favorites_count: int}
     */
    public function toggleFavorite(array $data): array
    {
        $post = Post::findOrFail($data['id']);

        $changes = $post->favoritedByUsers()->toggle([Auth::id()]);

        return [
            'favorited' => $changes['attached'] !== [],
            'favorites_count' => $post->favoritedByUsers()->count(),
        ];
    }

    /**
     * Consulta base de postagens com autor, imagens, contadores e o estado de
     * curtida/favorito do usuário autenticado — tudo que o PostResource expõe.
     */
    private function query(): Builder
    {
        $userId = Auth::id();

        return Post::query()
            ->with([
                'media',
                'user' => function ($query) {
                    $query->with(User::PROFILE_RELATIONS);
                },
            ])
            ->withCount(['likes', 'comments', 'favoritedByUsers as favorites_count'])
            ->withExists([
                'likes as is_liked' => function (Builder $query) use ($userId) {
                    $query->where('user_id', $userId);
                },
                'favoritedByUsers as is_favorited' => function (Builder $query) use ($userId) {
                    $query->where('users.id', $userId);
                },
            ]);
    }

    private function ensureAbility(string $ability, Post $post): void
    {
        if (Gate::denies($ability, $post)) {
            throw new ApiException('This post does not belong to you!', 403);
        }
    }
}
