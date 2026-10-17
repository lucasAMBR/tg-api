<?php

namespace App\Models;

use App\Traits\HasLikes;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasUuidV7, HasLikes;

    public const REMOVED_CONTENT = '[removed]';

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'post_id',
        'parent_id',
        'content',
        'original_content',
        'removed_at',
    ];

    protected $hidden = [
        'original_content',
    ];

    protected $casts = [
        'removed_at' => 'datetime',
    ];

    public function isRemoved(): bool
    {
        return $this->removed_at !== null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }
}
