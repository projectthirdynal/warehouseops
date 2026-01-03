<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Waybill extends Model
{
    protected $fillable = [
        'waybill_number', 'upload_id', 'lead_id', 'product_id', 'sender_name', 'sender_address', 'sender_phone',
        'receiver_name', 'receiver_address', 'receiver_phone', 'destination',
        'province', 'city', 'barangay', 'street',
        'weight', 'quantity', 'service_type', 'cod_amount', 'remarks', 'item_name', 'status', 'batch_ready', 'marked_pending_at', 'signing_time'
    ];

    protected $casts = [
        'signing_time' => 'datetime',
        'marked_pending_at' => 'datetime',
        'batch_ready' => 'boolean',
    ];

    public function upload(): BelongsTo
    {
        return $this->belongsTo(Upload::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the lead associated with this waybill.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
