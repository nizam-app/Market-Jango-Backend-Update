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
        'pickup_address',
        'drop_of_address',
        'distance',
        'tax_ref',
        'currency',
        'delivery_status',
        'payment_method',
        'status',
        'user_id'
    ];
    public function user(){
        return $this->belongsTo(User::class);
    }
    // invoice item
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
    public function statusLogs()
    {
        return $this->hasMany(InvoiceStatusLog::class);
    }
}
