<?php

namespace App\Traits;

use App\Models\Like;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasLikes
{
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Alterna a curtida do usuário e retorna se o conteúdo ficou curtido.
     */
    public function toggleLike(string $userId): bool
    {
        $like = $this->likes()->where('user_id', $userId)->first();

        if ($like) {
            $like->delete();

            return false;
        }

        $this->likes()->create(['user_id' => $userId]);

        return true;
    }
}
