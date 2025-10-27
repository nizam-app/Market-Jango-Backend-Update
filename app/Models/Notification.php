<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'message',
        'read'
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
