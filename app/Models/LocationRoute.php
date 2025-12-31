<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationRoute extends Model
{
    protected $guarded = [];

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function startPoint()
    {
        return $this->belongsTo(Location::class, 'start_point_id');
    }

    public function endPoint()
    {
        return $this->belongsTo(Location::class, 'end_point_id');
    }
}
