<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadCycle extends Model
{
    // Cycle Status Constants
    const STATUS_ACTIVE = 'ACTIVE';
    const STATUS_CLOSED_SALE = 'CLOSED_SALE';
    const STATUS_CLOSED_REJECT = 'CLOSED_REJECT';
    const STATUS_CLOSED_RETURNED = 'CLOSED_RETURNED';
    const STATUS_CLOSED_EXHAUSTED = 'CLOSED_EXHAUSTED';

    protected $fillable = [
        'lead_id',
        'agent_id',
        'cycle_number',
        'status',
        'opened_at',
        'closed_at',
        'call_attempts',
        'last_called_at',
        'notes',
        'waybill_id'
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_called_at' => 'datetime',
        'call_attempts' => 'integer',
        'cycle_number' => 'integer',
        'notes' => 'array', // JSON stored as array
    ];

    /**
     * Get the lead this cycle belongs to.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the agent assigned to this cycle.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Get the waybill associated with this cycle.
     */
    public function waybill(): BelongsTo
    {
        return $this->belongsTo(Waybill::class);
    }

    /**
     * Check if cycle is still active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if cycle ended in a sale.
     */
    public function isSale(): bool
    {
        return $this->status === self::STATUS_CLOSED_SALE;
    }

    /**
     * Add a structured note entry.
     */
    public function addNote(string $content, User $author, string $type = 'note'): void
    {
        $notes = $this->notes ?? [];
        
        $notes[] = [
            'timestamp' => now()->toIso8601String(),
            'author_id' => $author->id,
            'author_name' => $author->name,
            'type' => $type, // note, status_change, call
            'content' => $content
        ];
        
        $this->notes = $notes;
        $this->save();
    }

    /**
     * Record a call attempt.
     */
    public function recordCall(): void
    {
        $this->call_attempts++;
        $this->last_called_at = now();
        $this->save();
    }

    /**
     * Close the cycle with a specific outcome.
     */
    public function close(string $outcome): void
    {
        $this->status = $outcome;
        $this->closed_at = now();
        $this->save();
    }
}
