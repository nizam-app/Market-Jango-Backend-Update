<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Buyer extends Model
{
    protected $fillable = [

    ];
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }
}
