<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VariantValue extends Model
{
    protected $fillable = [
        'name',
        'product_variant_id',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
