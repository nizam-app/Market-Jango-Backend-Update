<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'message',
        'is_read',
        'sender_id',
        'receiver_id',
    ];

    // Who sent the notification
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Who will receive the notification
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
