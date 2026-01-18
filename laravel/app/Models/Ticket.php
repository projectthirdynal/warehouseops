<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ref_no',
        'user_id',
        'category_id',
        'assigned_to',
        'subject',
        'description',
        'priority',
        'status',
    ];

    // Status Constants
    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    // Priority Constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at', 'asc');
    }

    public function worklogs(): HasMany
    {
        return $this->hasMany(TicketWorklog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get CSS class for status badge
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_OPEN => 'bg-blue-100 text-blue-800',
            self::STATUS_IN_PROGRESS => 'bg-yellow-100 text-yellow-800',
            self::STATUS_RESOLVED => 'bg-green-100 text-green-800',
            self::STATUS_CLOSED => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getPriorityBadgeClass(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'start-low', // assuming custom/CSS
            self::PRIORITY_NORMAL => 'status-normal',
            self::PRIORITY_HIGH => 'status-high',
            self::PRIORITY_CRITICAL => 'status-critical',
            default => 'status-normal',
        };
    }
}
