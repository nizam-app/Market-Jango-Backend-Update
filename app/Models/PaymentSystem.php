<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSystem extends Model
{
    protected $fillable = [
        'public_key',
        'secret_key',
        'init_url',
        'currency',
        'success_url',
        'fail_url',
        'cancel_url',
        'ipn_url',
        'logo',
    ];
}
