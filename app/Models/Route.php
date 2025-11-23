<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    use HasFactory;

    protected $fillable = ['name','longitude', 'latitude'];

    public function locations()
    {
        return $this->hasMany(Location::class);

    }

}
