<?php

namespace App\Http\Resources\Question;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Trecho de código associado a uma questão ou a uma alternativa.
 *
 * A coluna `code_snippet` é um `jsonb` com o cast `AsCollection`, então o
 * Scramble não consegue inferir o formato sozinho — este resource existe para
 * declarar o contrato `{ code, language }` na spec.
 */
class CodeSnippetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{code: string, language: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'code' => (string) ($this->resource['code'] ?? ''),
            'language' => (string) ($this->resource['language'] ?? ''),
        ];
    }
}
