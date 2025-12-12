<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchScanItem extends Model
{
    protected $fillable = ['batch_session_id', 'waybill_number', 'scan_type', 'scan_time', 'error_message'];

    public function session()
    {
        return $this->belongsTo(BatchSession::class, 'batch_session_id');
    }
}
