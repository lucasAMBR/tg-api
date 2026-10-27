<?php

namespace App\Services\Post;

use App\Exceptions\ApiException;
use App\Http\Resources\Post\PostCollection;
use App\Http\Resources\Post\PostParticipantCollection;
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

    /** Pesos do score de popularidade: comentar engaja mais do que curtir. */
    private const POPULARITY_WEIGHTS = [
        'likes' => 1,
        'comments' => 3,
        'favorites' => 2,
    ];

    public function index(array $data): PostCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $search = $data['search'] ?? null;
        $userId = $data['user_id'] ?? null;
        $sort = $data['sort'] ?? 'recent';
        $feed = $data['feed'] ?? 'all';

        $posts = $this->query()
            ->when($userId, function (Builder $query, $userId) {
                $query->where('user_id', $userId);
            })
            ->when($feed === 'following', fn (Builder $query) => $this->onlyFollowing($query))
            ->when($search, function (Builder $query, $search) {
                $query->where('content', 'ILIKE', "%{$search}%");
            })
            ->when(
                $sort === 'popular',
                fn (Builder $query) => $this->orderByPopularity($query),
                fn (Builder $query) => $query->latest(),
            )
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
     * Quem está na conversa dos comentários da postagem: os autores de comentários e respostas,
     * cada um uma vez, do mais ativo para o menos, com a participação mais recente desempatando.
     *
     * Comentários removidos não contam, porque a árvore deixa de expor quem os escreveu, e os de
     * contas excluídas ficam sem autor. O autor da postagem só aparece se tiver comentado, e aí
     * vem marcado com is_post_author.
     */
    public function participants(array $data): PostParticipantCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;

        $post = Post::findOrFail($data['id']);

        // A participação é agregada antes de juntar com os usuários, para cada um aparecer uma
        // vez e a paginação contar pessoas, não comentários.
        $participation = DB::table('comments')
            ->where('post_id', $post->id)
            ->whereNull('removed_at')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->selectRaw('user_id, count(*) as total')
            ->selectRaw('min(created_at) as first_commented_at, max(created_at) as last_commented_at');

        $participants = User::query()
            ->joinSub($participation, 'participation', 'participation.user_id', '=', 'users.id')
            ->select('users.*')
            ->selectRaw('participation.total as comments_count')
            ->selectRaw('participation.first_commented_at as first_commented_at')
            ->selectRaw('participation.last_commented_at as last_commented_at')
            ->selectRaw('case when users.id = ? then 1 else 0 end as is_post_author', [$post->user_id])
            ->withCasts([
                'first_commented_at' => 'datetime',
                'last_commented_at' => 'datetime',
            ])
            ->with(User::PROFILE_RELATIONS)
            ->withFollowState(Auth::id())
            // O id desempata, para a paginação não embaralhar quem tem a mesma participação.
            ->orderByDesc('comments_count')
            ->orderByDesc('last_commented_at')
            ->orderBy('users.id')
            ->paginate($perPage, ['*'], 'page', $page);

        return new PostParticipantCollection($participants);
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

    /**
     * Das postagens com mais engajamento para as com menos. O score entra como
     * subconsulta com alias próprio porque o Postgres não aceita o alias de um
     * `withCount` dentro de uma expressão no ORDER BY — só como nome isolado.
     *
     * A data desempata, então postagens sem nenhum engajamento continuam saindo
     * da mais recente para a mais antiga e a paginação não embaralha.
     */
    private function orderByPopularity(Builder $query): Builder
    {
        $weights = self::POPULARITY_WEIGHTS;

        return $query
            ->selectRaw(
                '(
                    (select count(*) from likes
                        where likes.likeable_type = ? and likes.likeable_id = posts.id) * ?
                    + (select count(*) from comments where comments.post_id = posts.id) * ?
                    + (select count(*) from favorite_posts where favorite_posts.post_id = posts.id) * ?
                ) as popularity_score',
                [
                    (new Post)->getMorphClass(),
                    $weights['likes'],
                    $weights['comments'],
                    $weights['favorites'],
                ],
            )
            ->orderByDesc('popularity_score')
            ->latest();
    }

    /**
     * Como a aba "Seguindo" do Twitter: postagens de quem o usuário segue e as dele mesmo,
     * para que o que ele acabou de publicar apareça no feed que está vendo.
     */
    private function onlyFollowing(Builder $query): Builder
    {
        /** @var User $user */
        $user = Auth::user();

        return $query->where(function (Builder $query) use ($user) {
            $query->whereIn('user_id', $user->following()->select('users.id'))
                ->orWhere('user_id', $user->id);
        });
    }

    private function ensureAbility(string $ability, Post $post): void
    {
        if (Gate::denies($ability, $post)) {
            throw new ApiException('This post does not belong to you!', 403);
        }
    }
}
