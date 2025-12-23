<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SetWeight extends Model
{
      protected $fillable = [
        'max_weight',
        'weight_unit',
        'status',
    ];
        protected $casts = [
    'max_weight' => 'float',
    'status' => 'boolean',
];
}
