<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = ['buyer_id', 'status'];
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
