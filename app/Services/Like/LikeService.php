<?php

namespace App\Services\Like;

use App\Exceptions\ApiException;
use App\Http\Resources\Like\LikeCollection;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LikeService
{
    /**
     * @return array{liked: bool, likes_count: int}
     */
    public function togglePostLike(array $data): array
    {
        return $this->toggle(Post::findOrFail($data['id']));
    }

    /**
     * @return array{liked: bool, likes_count: int}
     */
    public function toggleCommentLike(array $data): array
    {
        $comment = Comment::findOrFail($data['id']);

        if ($comment->isRemoved()) {
            throw new ApiException('A removed comment cannot be liked!', 422);
        }

        return $this->toggle($comment);
    }

    public function indexPostLikes(array $data): LikeCollection
    {
        return $this->paginate(Post::findOrFail($data['id']), $data);
    }

    public function indexCommentLikes(array $data): LikeCollection
    {
        return $this->paginate(Comment::findOrFail($data['id']), $data);
    }

    /**
     * @return array{liked: bool, likes_count: int}
     */
    private function toggle(Post|Comment $likeable): array
    {
        return DB::transaction(function () use ($likeable) {
            $liked = $likeable->toggleLike(Auth::id());

            return [
                'liked' => $liked,
                'likes_count' => $likeable->likes()->count(),
            ];
        });
    }

    private function paginate(Post|Comment $likeable, array $data): LikeCollection
    {
        $page = $data['page'] ?? 1;
        $perPage = $data['per_page'] ?? 10;

        $likes = $likeable->likes()
            ->with([
                'user' => function ($query) {
                    $query->with(User::PROFILE_RELATIONS);
                },
            ])
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return new LikeCollection($likes);
    }
}
