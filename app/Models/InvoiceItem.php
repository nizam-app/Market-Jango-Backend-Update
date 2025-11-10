<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'vendor_id',
        'driver_id',
        'quantity',
        'sale_price',
        'tran_id'
    ];
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'invoice_id', 'id');
    }
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    public function rating()
    {
        return $this->hasOne(Review::class, 'invoice_item_id');
    }

}
