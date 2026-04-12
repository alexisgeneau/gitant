<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Billable, SoftDeletes;

    /** OAuth-only auth — no password-based remember tokens needed. */
    public $rememberTokenName = '';

    protected $fillable = [
        'username',
        'email',
        'github_id',
        'gitlab_id',
        'avatar_url',
        'stripe_connect_account_id',
        'stripe_connect_status',
        'preferred_locale',
        'is_admin',
        'reputation_score',
        'cooldown_until',
        'notification_preferences',
    ];

    protected $hidden = [
        'stripe_connect_account_id',
    ];

    protected function casts(): array
    {
        return [
            'is_admin'                  => 'boolean',
            'deleted_at'                => 'datetime',
            'cooldown_until'            => 'datetime',
            'notification_preferences'  => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function contributions(): HasMany
    {
        return $this->hasMany(BountyContribution::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Bounty::class, 'claimed_by_user_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isStripeConnectOnboarded(): bool
    {
        return $this->stripe_connect_status === 'active';
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatar_url;
    }

    public function isUnderCooldown(): bool
    {
        return $this->cooldown_until && $this->cooldown_until->isFuture();
    }

    public function adjustReputation(int $delta): void
    {
        $newScore = max(0, ($this->reputation_score ?? 100) + $delta);
        $this->update(['reputation_score' => $newScore]);
    }
}
