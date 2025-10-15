<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    protected $fillable = [
        'name',
        'product_variant_id',
    ];

    public function productVariant()
    {
        return $this->belongsTo(ProductAttribute::class);
    }

}
