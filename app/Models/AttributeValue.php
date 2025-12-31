<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    protected $guarded = [];

    public function productAttribute()
    {
        return $this->belongsTo(ProductAttribute::class);
    }

}
