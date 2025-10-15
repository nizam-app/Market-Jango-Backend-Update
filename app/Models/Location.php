<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function routes()
    {
        return $this->belongsToMany(Route::class, 'location_route', 'location_id', 'route_id')
            ->withPivot('sequence')
            ->orderBy('pivot_sequence');
    }
}
