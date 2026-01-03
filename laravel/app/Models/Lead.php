<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    const STATUS_NEW = 'NEW';
    const STATUS_CALLING = 'CALLING';
    const STATUS_NO_ANSWER = 'NO_ANSWER';
    const STATUS_REJECT = 'REJECT';
    const STATUS_CALLBACK = 'CALLBACK';
    const STATUS_SALE = 'SALE';
    const STATUS_REORDER = 'REORDER';
    const STATUS_DELIVERED = 'DELIVERED';
    const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'name',
        'phone',
        'address',
        'city',
        'state',
        'barangay',
        'street',
        'status',
        'source',
        'assigned_to',
        'uploaded_by',
        'original_agent_id',
        'last_called_at',
        'call_attempts',
        'notes',
        'signing_time',
        'submission_time',
        'submitted_at',
        'product_name',
        'product_brand',
        'previous_item',
        'amount',
        'assigned_at'
    ];

    protected $casts = [
        'last_called_at' => 'datetime',
        'call_attempts' => 'integer',
        'assigned_to' => 'integer',
        'uploaded_by' => 'integer',
        'signing_time' => 'datetime',
        'submission_time' => 'datetime',
        'assigned_at' => 'datetime'
    ];

    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(LeadLog::class)->orderBy('created_at', 'desc');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, [self::STATUS_SALE, self::STATUS_DELIVERED]);
    }

    public function isFinalized(): bool
    {
        return in_array($this->status, [self::STATUS_SALE, self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->orderBy('created_at', 'desc');
    }
}
