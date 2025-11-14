<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryCharge extends Model
{
    protected $fillable= [
        'vendor_id',
        'product_id',
        'delivery_charge',
        'quantity',
    ];
}
