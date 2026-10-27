<?php

namespace App\Http\Resources\Profiles\DevProfile;

use App\Enums\DevSpecialtyEnum;
use App\Enums\SeniorityLevelEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Perfil público do dev, exibido para outros usuários (ex.: o cliente vendo quem orçou
 * o freela). Diferente do `DevProfileResource`, não leva CPF, telefone nem data de
 * nascimento. Carregue a relação `user` junto para a foto não gerar N+1.
 */
class DevProfileSummaryResource extends JsonResource
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
            /**
             * Id do usuário (é o usado em `POST /chat` para abrir a conversa)
             */
            'user_id' => $this->user_id,
            'name' => $this->name,
            'profile_pic' => $this->user?->profile_pic['original_url'] ?? null,
            'bio' => $this->bio,
            'bio_pt' => $this->bio_pt,
            'bio_en' => $this->bio_en,
            'seniority_level' => $this->seniority_level,
            'seniority_level_label' => SeniorityLevelEnum::labelFromValue($this->seniority_level),
            'specialty' => $this->specialty,
            'specialty_label' => DevSpecialtyEnum::labelFromValue($this->specialty),
            'score' => $this->score,
            'open_to_work' => (bool) $this->open_to_work,
        ];
    }
}
