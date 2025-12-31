<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SetWeight extends Model
{
    protected $guarded = [];
        protected $casts = [
    'max_weight' => 'float',
    'status' => 'boolean',
];
}
