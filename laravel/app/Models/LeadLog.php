<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadLog extends Model
{
    protected $fillable = [
        'lead_id',
        'user_id',
        'action',      // status_change, note, call
        'old_status',
        'new_status',
        'description'
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
