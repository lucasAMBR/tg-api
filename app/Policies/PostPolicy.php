<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function update(User $user, Post $post): bool
    {
        return $post->user_id === $user->id;
    }

    /**
     * Admin pode remover qualquer postagem (moderação).
     */
    public function delete(User $user, Post $post): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return $post->user_id === $user->id;
    }
}
