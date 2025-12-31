<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $guarded = [];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
