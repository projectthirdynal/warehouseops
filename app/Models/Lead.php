<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'address',
        'city',
        'state',
        'status',          // NEW, CALLING, NO_ANSWER, REJECT, CALLBACK, NOT_INTERESTED, SALE, DELIVERED, CANCELLED
        'assigned_to',
        'uploaded_by',
        'last_called_at',
        'call_attempts',
        'notes'
    ];

    protected $casts = [
        'last_called_at' => 'datetime',
        'call_attempts' => 'integer',
        'assigned_to' => 'integer',
        'uploaded_by' => 'integer'
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
}
