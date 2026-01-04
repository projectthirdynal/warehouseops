<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLog extends Model
{
    protected $fillable = [
        'user_id',
        'lead_id',
        'phone_number',
        'call_id',
        'direction',
        'status',
        'started_at',
        'answered_at',
        'ended_at',
        'duration_seconds',
        'recording_url',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'metadata' => 'array',
        'duration_seconds' => 'integer',
    ];

    // -- Status Constants --
    const STATUS_INITIATED = 'initiated';
    const STATUS_RINGING = 'ringing';
    const STATUS_ANSWERED = 'answered';
    const STATUS_ENDED = 'ended';
    const STATUS_FAILED = 'failed';
    const STATUS_MISSED = 'missed';
    const STATUS_BUSY = 'busy';

    // -- Direction Constants --
    const DIRECTION_OUTBOUND = 'outbound';
    const DIRECTION_INBOUND = 'inbound';

    // -- Relationships --

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    // -- Accessors --

    /**
     * Get formatted duration (MM:SS or HH:MM:SS)
     */
    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->duration_seconds;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%02d:%02d', $minutes, $secs);
    }

    /**
     * Check if call was successful (answered)
     */
    public function getWasAnsweredAttribute(): bool
    {
        return $this->status === self::STATUS_ANSWERED || $this->status === self::STATUS_ENDED;
    }

    // -- Scopes --

    public function scopeForAgent($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', self::DIRECTION_OUTBOUND);
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', self::DIRECTION_INBOUND);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_INITIATED, self::STATUS_RINGING, self::STATUS_ANSWERED]);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_ENDED, self::STATUS_FAILED, self::STATUS_MISSED, self::STATUS_BUSY]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // -- Methods --

    /**
     * Mark call as answered
     */
    public function markAnswered(): void
    {
        $this->update([
            'status' => self::STATUS_ANSWERED,
            'answered_at' => now(),
        ]);
    }

    /**
     * End the call and calculate duration
     */
    public function endCall(?string $notes = null): void
    {
        $endTime = now();
        $duration = 0;

        if ($this->answered_at) {
            $duration = $endTime->diffInSeconds($this->answered_at);
        }

        $updateData = [
            'status' => self::STATUS_ENDED,
            'ended_at' => $endTime,
            'duration_seconds' => $duration,
        ];

        if ($notes) {
            $updateData['notes'] = $notes;
        }

        $this->update($updateData);

        // Update lead's last_called_at
        if ($this->lead) {
            $this->lead->update(['last_called_at' => $endTime]);
        }
    }

    /**
     * Mark call as failed
     */
    public function markFailed(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'ended_at' => now(),
            'metadata' => array_merge($this->metadata ?? [], ['failure_reason' => $reason]),
        ]);
    }
}
