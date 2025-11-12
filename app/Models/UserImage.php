<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserImage extends Model
{
    protected $fillable = [
        'image_path',
        'user_id',
        'user_type',
        'file_type',
        'public_id'
    ];
}
