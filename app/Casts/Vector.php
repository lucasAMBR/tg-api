<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Converte entre o array de floats usado no PHP e o formato textual que o
 * pgvector espera na coluna `vector` (`[0.1,0.2,...]`).
 */
class Vector implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        return json_decode($value, true);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (is_null($value)) {
            return null;
        }

        // Já veio no formato textual do pgvector
        if (is_string($value)) {
            return $value;
        }

        if (!is_array($value)) {
            throw new InvalidArgumentException("O atributo [{$key}] precisa ser um array de floats ou null.");
        }

        return '[' . implode(',', array_map(fn($number) => (float) $number, $value)) . ']';
    }
}
