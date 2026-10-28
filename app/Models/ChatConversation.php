<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Conversa do chat. É sempre 1:1 (não existe conversa em grupo), entre usuários de
 * qualquer tipo de perfil
 */
class ChatConversation extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function lastMessage(): HasOne
    {
        // Ordena em vez de usar latestOfMany(), que desempata com MAX(id) e o Postgres
        // não tem MAX() para uuid. No eager load, o limit(1) vira um limite por conversa,
        // para a listagem não carregar o histórico inteiro de cada uma
        return $this->hasOne(ChatMessage::class, 'conversation_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(1);
    }

    public function scopeOfParticipant(Builder $query, string $userId)
    {
        return $query->where(fn (Builder $query) => $query
            ->where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
        );
    }

    public function otherParticipantId(string $userId): string
    {
        return $this->user_one_id === $userId ? $this->user_two_id : $this->user_one_id;
    }

    public function otherParticipant(string $userId): User
    {
        return $this->user_one_id === $userId ? $this->userTwo : $this->userOne;
    }
}
