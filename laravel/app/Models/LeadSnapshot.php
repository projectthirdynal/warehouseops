<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadSnapshot extends Model
{
    public $timestamps = false; // We only use created_at

    protected $fillable = [
        'lead_id',
        'snapshot_data',
        'reason',
        'created_at'
    ];

    protected $casts = [
        'snapshot_data' => 'array',
        'created_at' => 'datetime'
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
