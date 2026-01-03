<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentFlag extends Model
{
    const SEVERITY_INFO = 'INFO';
    const SEVERITY_WARNING = 'WARNING';
    const SEVERITY_CRITICAL = 'CRITICAL';

    const TYPE_RECYCLE_ABUSE = 'RECYCLE_ABUSE';
    const TYPE_SLOW_CONTACT = 'SLOW_CONTACT';
    const TYPE_LOW_CONVERSION = 'LOW_CONVERSION';
    const TYPE_HIGH_IDLE = 'HIGH_IDLE';

    protected $fillable = [
        'user_id',
        'type',
        'severity',
        'metric_value',
        'team_average',
        'details',
        'is_resolved'
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'details' => 'array'
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
