<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportInvoice extends Model
{
    protected $fillable = [
        'total',
        'vat',
        'payable',
        'cus_name',
        'cus_email',
        'cus_phone',
        'pickup_address',
        'drop_of_address',
        'delivery_status',
        'distance',
        'status',
        'tax_ref',
        'currency',
        'user_id',
    ];
}
