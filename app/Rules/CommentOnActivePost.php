<?php

namespace App\Rules;

use App\Models\Comment;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CommentOnActivePost implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // O whereHas aplica o soft delete do Post, descartando comentários de postagens removidas.
        $exists = Comment::whereKey($value)->whereHas('post')->exists();

        if (!$exists) {
            $fail('O comentário informado não existe ou a postagem dele foi removida.');
        }
    }
}
