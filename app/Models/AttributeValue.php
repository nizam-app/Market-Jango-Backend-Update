<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    protected $fillable = [
        'name',
        'product_attribute_id',
        'vendor_id'
    ];

    public function productAttribute()
    {
        return $this->belongsTo(ProductAttribute::class);
    }

}
