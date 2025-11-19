<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Buyer extends Model
{
    protected $fillable = [
        'gender',
        'age',
        'address',
        'state',
        'postcode',
        'country',
        'ship_name',
        'ship_email',
        'ship_location',
        'ship_latitude',
        'ship_longitude',
        'ship_country',
        'ship_phone',
        'description',
        'location',
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
