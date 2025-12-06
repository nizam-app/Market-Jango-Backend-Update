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
        'pickup_longitude',
        'pickup_latitude',
        'ship_address',
        'ship_latitude',
        'current_latitude',
        'current_longitude',
        'current_address',
        'ship_longitude',
        'total_pay',
        'payment_proof_id',
        'delivery_charge',
        'user_id',
        'quantity',
        'distance',
        'note',
        'payment_method',
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
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
