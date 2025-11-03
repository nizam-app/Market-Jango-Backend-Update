<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'user_id',
        'quantity',
        'sale_price',
        'tran_id'
    ];
}
