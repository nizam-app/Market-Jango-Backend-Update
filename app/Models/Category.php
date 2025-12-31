<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $guarded = [];
        public function vendors()
    {
        return $this->belongsToMany(Vendor::class, 'category_vendor');
    }
    // public function vendors()
    // {
    //     return $this->belongsToMany(Vendor::class, 'category_vendor')
    //         ->withPivot('priority')
    //         ->withTimestamps();
    // }
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
