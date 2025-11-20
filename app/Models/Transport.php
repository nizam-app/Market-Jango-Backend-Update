<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transport extends Model
{
    protected $fillable = [
        'user_id',
        'address',
        'longitude',
        'latitude',
        ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
