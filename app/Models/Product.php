<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'regular_price',
        'sell_price',
        'discount',
        'public_id',
        'star',
        'image',
        'color',
        'size',
        'remark',
        'is_active',
        'vendor_id',
        'category_id'
    ];
    protected $casts = [
        'color' => 'array',
        'size' => 'array',
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
        return $this->hasOne(ProductBanner::class);
    }
    public function variants()
    {
        return $this->hasMany(ProductAttribute::class);
    }
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }
}
