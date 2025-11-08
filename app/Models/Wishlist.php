<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $fillable = ['buyer_id', 'product_id'];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function buyers()
    {
        return $this->belongsTo(Buyer::class);
    }

}
