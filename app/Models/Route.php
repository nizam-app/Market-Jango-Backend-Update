<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    use HasFactory;

       protected $guarded = [];
    public function locations()
    {
        return $this->hasMany(Location::class);

    }
    public function drivers()
    {
        return $this->belongsToMany(Driver::class, 'driver_route');
    }

    // public function driver()
    // {
    //     return $this->hasMany(Driver::class);

    // }

}
