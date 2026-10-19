<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden([
    'password',
    'remember_token',
    'totp_secret',
    'totp_recovery_codes',
    'totp_last_code_fingerprint',
])]
class User extends Authenticatable
{
    protected $attributes = [
        'is_active' => true,
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->totp_enabled_at !== null && is_string($this->totp_secret);
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
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
            'totp_secret' => 'encrypted',
            'totp_recovery_codes' => 'array',
            'totp_enabled_at' => 'datetime',
            'totp_last_code_used_at' => 'datetime',
        ];
    }
}
