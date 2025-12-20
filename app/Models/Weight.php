<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Weight extends Model
{
        protected $fillable = [
        'min_weight',
        'max_weight',
        'weight_unit',
        'delivery_charge',
        'status',
    ];
    protected $casts = [
    'min_weight' => 'float',
    'max_weight' => 'float',
    'delivery_charge' => 'float',
    'status' => 'boolean',
];

}
