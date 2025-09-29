<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'country',
        'address',
        'business_name',
        'business_type',
        'user_id'
    ];
}
