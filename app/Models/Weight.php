<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Weight extends Model
{
       protected $guarded = [];
    protected $casts = [
    'min_weight' => 'float',
    'max_weight' => 'float',
    'delivery_charge' => 'float',
    'status' => 'boolean',
];

}
