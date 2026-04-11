<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessedStripeEvent extends Model
{
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'stripe_event_id';

    protected $fillable = [
        'stripe_event_id',
        'event_type',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public static function hasProcessed(string $eventId): bool
    {
        return static::where('stripe_event_id', $eventId)->exists();
    }

    public static function markProcessed(string $eventId, string $eventType): static
    {
        return static::create([
            'stripe_event_id' => $eventId,
            'event_type'      => $eventType,
            'processed_at'    => now(),
        ]);
    }
}
