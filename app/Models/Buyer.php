<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Buyer extends Model
{
    protected $fillable = [

        'gender',
        'age',
        'description',
        'location',
        'image',
        'user_id',
    ];
    public function cart()
    {
        return $this->hasMany(Cart::class);
    }
    public function wishList()
    {
        return $this->hasMany(Wishlist::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
