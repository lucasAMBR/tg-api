<?php

namespace App\Http\Resources\Language;

use App\Models\JobVacancyLanguage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'language_level' => $this->whenPivotLoaded(JobVacancyLanguage::class, fn() => $this->pivot->language_level),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];

        // Como passo um array contendo tanto a linguagem quanto o level e retorno esse resource no campo de language
        // eu devo passar o language_level por aqui e fazer toda a lógica de tabela pivot para retornar depois 
        // no resource de vagas 
    }
}
