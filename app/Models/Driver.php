<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'car_name',
        'car_model',
        'location',
        'price',
        'rating',
        'route_id',
        'user_id'
    ];
}
