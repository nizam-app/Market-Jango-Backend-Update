<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'total',
        'vat',
        'payable',
        'cus_name',
        'cus_email',
        'cus_phone',
        'ship_address',
        'ship_city',
        'ship_country',
        'tax_ref',
        'currency',
        'delivery_status',
        'payment_status',
        'user_id'
    ];
}
