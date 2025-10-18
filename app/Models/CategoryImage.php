<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryImage extends Model
{
    protected $fillable = [
        'image_path',
        'category_id',
        'vendor_id',
        'public_id'
    ];

    public function category(){
        return $this->belongsToMany(Category::class);
    }

}
