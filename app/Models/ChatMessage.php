<?php

namespace App\Models;

use App\Traits\HasUuidV7;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasUuidV7;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'body',
        'read_at',
        'read_receipt_hidden',
    ];

    protected $attributes = [
        'read_receipt_hidden' => false,
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'read_receipt_hidden' => 'boolean',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Quando a mensagem foi lida, do ponto de vista de quem está vendo. O destinatário
     * sempre enxerga a própria leitura; o remetente só enxerga se o destinatário leu com a
     * confirmação de leitura ligada e se ele próprio também está com a dele ligada (é
     * recíproco, como no WhatsApp: quem desliga também deixa de ver a leitura dos outros)
     */
    public function readAtFor(?User $viewer): ?CarbonInterface
    {
        if (!$viewer) {
            return null;
        }

        if ($viewer->id !== $this->sender_id) {
            return $this->read_at;
        }

        if ($this->read_receipt_hidden || !$viewer->read_receipts_enabled) {
            return null;
        }

        return $this->read_at;
    }
}
