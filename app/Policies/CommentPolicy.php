<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function update(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }

    /**
     * Admin pode remover qualquer comentário (moderação).
     */
    public function delete(User $user, Comment $comment): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $comment->user_id === $user->id;
    }
}
