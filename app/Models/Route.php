<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'location_route', 'route_id', 'location_id')
            ->withPivot('sequence')
            ->orderBy('pivot_sequence');
    }

}
