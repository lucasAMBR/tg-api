<?php

namespace App\Services\Follow;

use App\Exceptions\ApiException;
use App\Http\Resources\Follow\FollowCollection;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Seguidores no estilo Twitter: a relação é entre usuários (não entre perfis) e é unilateral,
 * sem aprovação, ou seja, seguir alguém não faz essa pessoa seguir de volta.
 */
class FollowService
{
    /**
     * @return array{following: bool, followers_count: int}
     */
    public function toggle(array $data): array
    {
        $user = User::findOrFail($data['id']);

        if ($user->id === Auth::id()) {
            throw new ApiException('You cannot follow yourself!', 422);
        }

        return DB::transaction(function () use ($user) {
            $changes = $user->followers()->toggle([Auth::id()]);

            return [
                'following' => $changes['attached'] !== [],
                'followers_count' => $user->followers()->count(),
            ];
        });
    }

    public function indexFollowers(array $data): FollowCollection
    {
        return $this->paginate(User::findOrFail($data['id'])->followers(), $data);
    }

    public function indexFollowing(array $data): FollowCollection
    {
        return $this->paginate(User::findOrFail($data['id'])->following(), $data);
    }

    /**
     * Do follow mais recente para o mais antigo, cada usuário com a relação que tem com
     * quem está vendo a lista, para o front exibir "Seguir"/"Seguindo" e "Segue você".
     */
    private function paginate(BelongsToMany $relation, array $data): FollowCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;

        $users = $relation
            ->with(User::PROFILE_RELATIONS)
            ->withFollowState(Auth::id())
            ->orderByPivot('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return new FollowCollection($users);
    }
}
