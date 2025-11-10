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
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function images()
    {
        return $this->hasMany(UserImage::class, 'user_id', 'id');
    }
    public function route()
    {
        return $this->belongsTo(Route::class);
    }


}
