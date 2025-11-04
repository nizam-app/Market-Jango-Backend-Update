<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBanner extends Model
{
    protected $fillable = [
        'image',
        'public_id'
    ];
    public function product(){
        return $this->belongsTo(Product::class);
    }

}
