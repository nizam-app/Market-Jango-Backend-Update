<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceStatusLog extends Model
{

    use HasFactory;

    protected $fillable = ['invoice_id', 'status', 'note','is_active','invoice_item_id','driver_id'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
    public function invoiceItem()
    {
        return $this->hasMany(InvoiceItem::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }  public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
