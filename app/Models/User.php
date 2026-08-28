<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\HasPermissions;
use App\Traits\HasUuidV7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject, HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasUuidV7, HasFactory, Notifiable, HasRoles, InteractsWithMedia;

    protected $guard_name = 'api';
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'admin_active_profile',
        'password',
        'is_blocked'
    ];

    protected $appends = ['profile_pic'];

    protected $with = ['media'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_blocked' => 'boolean',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_pic')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->format('webp')
            ->quality(80);
    }

    /**
     * @return array{id: int, original_url: string, thumb_url: string}|null
     */
    public function getProfilePicAttribute(): ?array
    {
        $media = $this->getFirstMedia('profile_pic');

        if (!$media) {
            return null;
        }

        return [
            'id'           => (int) $media->id,
            'original_url' => (string) $media->getUrl(),
            'thumb_url'    => (string) $media->getUrl('thumb'),
        ];
    }

    public function dev_profile(): HasOne
    {
        return $this->hasOne(DevProfile::class);
    }

    public function company_profile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class);
    }

    public function client_profile(): HasOne
    {
        return $this->hasOne(ClientProfile::class);
    }

    public function admin_profile(): HasOne
    {
        return $this->hasOne(AdminProfile::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
