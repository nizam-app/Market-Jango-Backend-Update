<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
        'is_top_category',
        'vendor_id'
    ];
    public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'category_vendor')
            ->withPivot('priority')
            ->withTimestamps();
    }
//    public function vendor()
//    {
//        return $this->belongsTo(Vendor::class);
//    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function categoryImages()
    {
        return $this->hasMany(CategoryImage::class);
    }

}
