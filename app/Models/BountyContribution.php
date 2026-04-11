<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BountyContribution extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'bounty_id',
        'user_id',
        'amount_cents',
        'commission_cents',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'commission_cents' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function bounty(): BelongsTo
    {
        return $this->belongsTo(Bounty::class);
    }

    public function funder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function totalChargedCents(): int
    {
        return $this->amount_cents + $this->commission_cents;
    }
}
