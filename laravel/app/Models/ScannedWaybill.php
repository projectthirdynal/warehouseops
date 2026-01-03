<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScannedWaybill extends Model
{
    protected $fillable = ['waybill_number', 'scanned_by', 'scan_date', 'batch_session_id'];

    public function session()
    {
        return $this->belongsTo(BatchSession::class, 'batch_session_id');
    }
}
