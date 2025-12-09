<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BatchSession extends Model
{
    protected $fillable = ['scanned_by', 'start_time', 'end_time', 'status', 'total_scanned', 'duplicate_count', 'error_count'];

    public function scans()
    {
        return $this->hasMany(BatchScanItem::class);
    }
    
    public function scannedWaybills()
    {
        return $this->hasMany(ScannedWaybill::class);
    }
}
