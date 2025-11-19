<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = [
        'cus_name',
        'cus_email',
        'cus_phone',
        'pickup_address',
        'ship_address',
        'delivery_charge',
        'user_id',
        'quantity',
        'distance',
        'sale_price',
        'status',
        'invoice_id',
        'product_id',
        'vendor_id',
        'driver_id',
        'tran_id'
    ];
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    public function statusLogs()
    {
        return $this->belongsTo(InvoiceStatusLog::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
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
