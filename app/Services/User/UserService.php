<?php

namespace App\Services\User;

use App\Exceptions\ApiException;
use App\Http\Resources\User\PopularUserCollection;
use App\Http\Resources\User\UserResource;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /** Relação de perfil que cada valor de profile_type exige no ranking de popularidade. */
    private const PROFILE_TYPE_RELATIONS = [
        'dev' => 'dev_profile',
        'company' => 'company_profile',
        'client' => 'client_profile',
    ];

    public function update(array $data): UserResource
    {
        $user = User::findOrFail($data['id']);

        $this->ensureAuthorized('update', $user);

        $authUser = Auth::user();

        $data = Arr::except($data, ['id']);

        $profilePic = Arr::pull($data, 'profile_pic');

        return DB::transaction(function () use ($authUser, $user, $data, $profilePic) {
            $user->fill($data);

            // 2. Lógica de senha (Melhorada)
            if (isset($data['new_password'])) {
                if (Hash::check($data['old_password'], $authUser->password)) {
                    $user->password = Hash::make($data['new_password']);
                }else{
                    throw new ApiException("Incorrect actual password!");
                }
            }

            // 3. Salva as alterações de fato
            $user->save();

            // 4. A coleção é singleFile(), então o upload substitui a foto anterior
            if ($profilePic instanceof UploadedFile) {
                $user->addMedia($profilePic)->toMediaCollection('profile_pic');

                $user->load('media');
            }

            // 5. Retorna a instância do Model $user para o Resource
            return new UserResource($user);
        });
    }

    public function blockUserAccess(array $data)
    {
        $user = User::findOrFail($data['id']);

        $user->update([
            'is_blocked' => true
        ]);

        return new UserResource($user);
    }

    public function unblockUserAccess(array $data)
    {
        $user = User::findOrFail($data['id']);

        $user->update([
            'is_blocked' => false
        ]);

        return new UserResource($user);
    }

    public function delete(array $data)
    {
        $user = User::findOrFail($data['id']);

        $this->ensureAuthorized('delete', $user);

        $user->delete();
    }

    /**
     * Ranking de popularidade: curtidas, comentários (com as respostas) e favoritos que os posts
     * de cada usuário receberam, somados ao número de seguidores.
     *
     * Só conta o engajamento vindo de outras pessoas (o autor curtindo ou respondendo no próprio
     * post não soma), sem comentários removidos nem posts excluídos. Ficam de fora usuários
     * bloqueados e os que não têm perfil de dev, empresa ou cliente.
     */
    public function indexPopular(array $data): PopularUserCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;
        $profileType = $data['profile_type'] ?? null;

        $profileRelations = $profileType
            ? [self::PROFILE_TYPE_RELATIONS[$profileType]]
            : self::PROFILE_TYPE_RELATIONS;

        // Cada total é agregado uma vez numa subconsulta e somado aqui, em vez de um withCount
        // por usuário: o Postgres não aceita alias de withCount dentro da soma do ORDER BY.
        $users = User::query()
            ->leftJoinSub($this->receivedLikes(), 'received_likes', 'received_likes.user_id', '=', 'users.id')
            ->leftJoinSub($this->receivedComments(), 'received_comments', 'received_comments.user_id', '=', 'users.id')
            ->leftJoinSub($this->receivedFavorites(), 'received_favorites', 'received_favorites.user_id', '=', 'users.id')
            ->leftJoinSub($this->followerTotals(), 'follower_totals', 'follower_totals.user_id', '=', 'users.id')
            ->select('users.*')
            ->selectRaw('coalesce(received_likes.total, 0) as received_likes_count')
            ->selectRaw('coalesce(received_comments.total, 0) as received_comments_count')
            ->selectRaw('coalesce(received_favorites.total, 0) as received_favorites_count')
            ->selectRaw('coalesce(follower_totals.total, 0) as followers_count')
            ->selectRaw(
                'coalesce(received_likes.total, 0) + coalesce(received_comments.total, 0)'
                . ' + coalesce(received_favorites.total, 0) + coalesce(follower_totals.total, 0) as popularity_score'
            )
            ->with(User::PROFILE_RELATIONS)
            ->withFollowState(Auth::id())
            ->where('users.is_blocked', false)
            ->where(function (Builder $query) use ($profileRelations) {
                foreach ($profileRelations as $relation) {
                    $query->orHas($relation);
                }
            })
            // Seguidores e id desempatam, para a paginação não embaralhar quem tem a mesma pontuação.
            ->orderByDesc('popularity_score')
            ->orderByDesc('followers_count')
            ->orderBy('users.id')
            ->paginate($perPage, ['*'], 'page', $page);

        return new PopularUserCollection($users);
    }

    /**
     * Curtidas que os posts de cada usuário receberam de outras pessoas.
     */
    private function receivedLikes(): QueryBuilder
    {
        return DB::table('likes')
            ->join('posts', 'posts.id', '=', 'likes.likeable_id')
            ->where('likes.likeable_type', (new Post)->getMorphClass())
            ->whereNull('posts.deleted_at')
            ->whereColumn('likes.user_id', '!=', 'posts.user_id')
            ->groupBy('posts.user_id')
            ->selectRaw('posts.user_id as user_id, count(*) as total');
    }

    /**
     * Comentários e respostas que os posts de cada usuário receberam de outras pessoas, sem os removidos.
     */
    private function receivedComments(): QueryBuilder
    {
        return DB::table('comments')
            ->join('posts', 'posts.id', '=', 'comments.post_id')
            ->whereNull('posts.deleted_at')
            ->whereNull('comments.removed_at')
            // Comentário de conta excluída fica sem autor e continua contando.
            ->where(function (QueryBuilder $query) {
                $query->whereNull('comments.user_id')
                    ->orWhereColumn('comments.user_id', '!=', 'posts.user_id');
            })
            ->groupBy('posts.user_id')
            ->selectRaw('posts.user_id as user_id, count(*) as total');
    }

    /**
     * Vezes que os posts de cada usuário foram favoritados por outras pessoas.
     */
    private function receivedFavorites(): QueryBuilder
    {
        return DB::table('favorite_posts')
            ->join('posts', 'posts.id', '=', 'favorite_posts.post_id')
            ->whereNull('posts.deleted_at')
            ->whereColumn('favorite_posts.user_id', '!=', 'posts.user_id')
            ->groupBy('posts.user_id')
            ->selectRaw('posts.user_id as user_id, count(*) as total');
    }

    private function followerTotals(): QueryBuilder
    {
        return DB::table('follows')
            ->groupBy('followed_id')
            ->selectRaw('followed_id as user_id, count(*) as total');
    }

    private function ensureAuthorized(string $ability, User $user): void
    {
        if (Gate::denies($ability, $user)) {
            throw new ApiException('You are not allowed to manage this account!', 403);
        }
    }
}
