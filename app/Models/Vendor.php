<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'country',
        'document',
        'user_id',
        'setting_id',
        'business_type_id'
    ];
}
