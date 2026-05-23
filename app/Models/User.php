<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'phone',
    'type',
    'role',
    'agent_code',
    'password',
    'otp_code',
    'otp_sent_at',
    'housing_area_id',
    'address',
    'landmark',
    'latitude',
    'longitude',
    'bank_name',
    'bank_account_name',
    'bank_account_number',
])]
#[Hidden(['password', 'remember_token', 'otp_code'])]
class User extends Authenticatable
{
    public const AUTH_TYPE_EMAIL = 'email';

    public const AUTH_TYPE_PHONE = 'phone';

    public const ROLE_BUYER = 'buyer';

    public const ROLE_SELLER = 'seller';

    public const ROLE_FINANCE = 'finance';

    public const ROLE_AGENT = 'agent';

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public static function roles(): array
    {
        return [
            self::ROLE_BUYER,
            self::ROLE_SELLER,
            self::ROLE_FINANCE,
            self::ROLE_AGENT,
        ];
    }

    public static function generateAgentCode(): string
    {
        do {
            $code = 'KA-'.random_int(10000, 99999);
        } while (self::query()->where('agent_code', $code)->exists());

        return $code;
    }

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

    public function agentTenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'agent_user_id');
    }

    public function agentCommissionWithdrawals(): HasMany
    {
        return $this->hasMany(AgentCommissionWithdrawal::class, 'agent_user_id');
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

    public function housingArea(): BelongsTo
    {
        return $this->belongsTo(HousingArea::class);
    }
}
