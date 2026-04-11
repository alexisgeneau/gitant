<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispute extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'bounty_id',
        'opened_by_user_id',
        'type',
        'opener_summary',
        'opener_evidence',
        'opener_demand',
        'respondent_position',
        'respondent_evidence',
        'respondent_replied_at',
        'status',
        'resolution',
        'resolved_by_user_id',
        'resolution_notes',
        'resolved_at',
        'response_deadline_at',
    ];

    protected function casts(): array
    {
        return [
            'opener_evidence'       => 'array',
            'respondent_evidence'   => 'array',
            'respondent_replied_at' => 'datetime',
            'resolved_at'           => 'datetime',
            'response_deadline_at'  => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function bounty(): BelongsTo
    {
        return $this->belongsTo(Bounty::class);
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['resolved'], true);
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Whether the resolution favours the opener.
     */
    public function openerWon(): bool
    {
        return in_array($this->resolution, ['paid_full', 'refunded_full'], true);
    }
}
