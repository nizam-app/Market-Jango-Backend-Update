<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'previous_price',
        'current_price',
        'image',
        'vendor_id',
        'category_id'
    ];
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function images(){
        return $this->hasMany(ProductImage::class);
    }
    public function banner(){
        return $this->hasOne(Banner::class);
    }
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
