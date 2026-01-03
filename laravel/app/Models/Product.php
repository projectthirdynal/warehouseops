<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku', 'name', 'description', 'category', 'unit_of_measure', 
        'minimum_stock_level', 'reorder_point', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function waybills()
    {
        return $this->hasMany(Waybill::class);
    }
}
