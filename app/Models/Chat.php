<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'message',
        'image',
        'public_id',
        'is_read',
        'reply_to',
        'sender_id',
        'receiver_id',
        'is_offer',
        'offer_id',
    ];
    // Optional: sender and receiver relationships
    public function sender() {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver() {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function replyTo() {
        return $this->belongsTo(Chat::class, 'reply_to');
    }
    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }
}
