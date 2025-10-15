<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    protected $fillable = [
        'name',
        'product_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function variantValues()
    {
        return $this->hasMany(VariantValue::class);
    }


}
