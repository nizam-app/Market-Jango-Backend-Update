<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBanner extends Model
{
    protected $fillable = [
        'name',
        'description',
        'discount',
        'image',
        'product_id'
    ];
    public function product(){
        return $this->belongsTo(Product::class);
    }

}
