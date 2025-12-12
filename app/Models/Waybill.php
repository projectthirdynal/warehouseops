<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Waybill extends Model
{
    protected $fillable = [
        'waybill_number', 'upload_id', 'sender_name', 'sender_address', 'sender_phone',
        'receiver_name', 'receiver_address', 'receiver_phone', 'destination',
        'weight', 'quantity', 'service_type', 'cod_amount', 'remarks', 'status', 'batch_ready', 'marked_pending_at', 'signing_time'
    ];

    protected $casts = [
        'signing_time' => 'datetime',
        'marked_pending_at' => 'datetime',
        'batch_ready' => 'boolean',
    ];

    public function upload()
    {
        return $this->belongsTo(Upload::class);
    }
}
