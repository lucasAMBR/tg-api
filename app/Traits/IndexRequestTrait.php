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

    protected function prepareBooleanQueryParams(array $fields): void
    {
        $merge = [];

        foreach ($fields as $field) {
            if (!$this->exists($field)) {
                continue;
            }

            $normalized = $this->normalizeQueryBoolean($this->input($field));

            if ($normalized !== null) {
                $merge[$field] = $normalized;
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    private function normalizeQueryBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        return match (strtolower(trim($value))) {
            'true', '1', 'on', 'yes' => true,
            'false', '0', 'off', 'no', '' => false,
            default => null,
        };
    }
}
