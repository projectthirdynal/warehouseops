<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $fillable = ['filename', 'uploaded_by', 'total_rows', 'processed_rows', 'status', 'notes', 'file_content'];

    public function waybills()
    {
        return $this->hasMany(Waybill::class);
    }
}
