<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Billable, SoftDeletes;

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
    ];

    protected $hidden = [
        'stripe_connect_account_id',
    ];

    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    // Will be populated when Bounty model exists
    // public function postedBounties(): HasMany { ... }
    // public function claims(): HasMany { ... }

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
}
