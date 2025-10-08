<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = [
        'quantity',
        'color',
        'size',
        'price',
        'qty',
        'product_id',
        'buyer_id',
        'vendor_id',
        'status',
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    // Cart belongs to a buyer
    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }

    // Cart has many items
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }
}
