<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Bounty extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'issue_url',
        'issue_platform',
        'issue_repo_owner',
        'issue_repo_name',
        'issue_number',
        'issue_title',
        'issue_description',
        'issue_labels',
        'issue_language',
        'status',
        'total_amount_cents',
        'claimed_by_user_id',
        'claimed_at',
        'claim_expires_at',
        'linked_pr_url',
        'linked_pr_platform',
        'linked_pr_number',
        'pr_submitted_at',
        'auto_validate_at',
        'expires_at',
        'public_message',
    ];

    protected function casts(): array
    {
        return [
            'issue_labels' => 'array',
            'claimed_at' => 'datetime',
            'claim_expires_at' => 'datetime',
            'pr_submitted_at' => 'datetime',
            'auto_validate_at' => 'datetime',
            'expires_at' => 'datetime',
            'total_amount_cents' => 'integer',
            'issue_number' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    public function claimer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'claimed_by_user_id');
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(BountyContribution::class);
    }

    public function paidContributions(): HasMany
    {
        return $this->hasMany(BountyContribution::class)->where('status', 'paid');
    }

    public function activeDispute(): HasOne
    {
        return $this->hasOne(Dispute::class)->whereIn('status', ['open', 'awaiting_response', 'mediation', 'arbitration']);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClaimed(): bool
    {
        return $this->status === 'claimed';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function totalAmountInDollars(): float
    {
        return $this->total_amount_cents / 100;
    }

    public function platformLabel(): string
    {
        return match ($this->issue_platform) {
            'github' => 'GitHub',
            'gitlab' => 'GitLab',
            default => ucfirst($this->issue_platform),
        };
    }

    public function fullRepoName(): string
    {
        return "{$this->issue_repo_owner}/{$this->issue_repo_name}";
    }
}
