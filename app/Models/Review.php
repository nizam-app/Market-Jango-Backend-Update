<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $guarded = [];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

}
