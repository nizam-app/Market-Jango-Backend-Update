<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $fillable = [
        'user_id',
        'date_of_birth',
        'present_address',
        'permanent_address',
        'role',
        'city',
        'postal_code',
        'country',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function role()
    {
        return $this->belongsTo(role::class);
    }
}
