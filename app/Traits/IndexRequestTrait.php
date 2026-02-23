<?php

namespace App\Traits;

trait IndexRequestTrait
{
    public function paginationRules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string']
        ];
    }
}
