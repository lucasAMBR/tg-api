<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Converte entre o array de floats usado no PHP e o literal que o pgvector espera ("[1,2,3]").
 *
 * @implements CastsAttributes<array<int, float>, array<int, float>>
 */
class Vector implements CastsAttributes
{
    /**
     * @return array<int, float>|null
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        return array_map(floatval(...), explode(',', trim($value, '[]')));
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            return $value;
        }

        return '[' . implode(',', $value) . ']';
    }
}
