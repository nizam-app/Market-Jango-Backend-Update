<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $guarded = [];
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
