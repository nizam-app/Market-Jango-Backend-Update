<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSelect extends Model
{
    protected $fillable = [
        'key',
        'product_id'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
