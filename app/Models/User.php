<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

#[Fillable([
    'name',
    'email',
    'phone',
    'type',
    'role',
    'password',
    'otp_code',
    'otp_sent_at',
    'housing_area',
    'address',
    'landmark',
    'latitude',
    'longitude',
])]
#[Hidden(['password', 'remember_token', 'otp_code'])]
class User extends Authenticatable implements FilamentUser
{
    public const AUTH_TYPE_EMAIL = 'email';
    public const AUTH_TYPE_PHONE = 'phone';
    public const ROLE_BUYER = 'buyer';
    public const ROLE_SELLER = 'seller';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'otp_sent_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function sessionTokens(): HasMany
    {
        return $this->hasMany(UserSessionToken::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function ownedTenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'owner_user_id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(UserDevice::class);
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === self::ROLE_SELLER;
    }
}
