<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'vendor_id',
        'quantity',
        'sale_price',
        'tran_id'
    ];
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }
    public function product()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }
    public function rating()
    {
        return $this->hasOne(Rating::class, 'invoice_item_id');
    }

}
