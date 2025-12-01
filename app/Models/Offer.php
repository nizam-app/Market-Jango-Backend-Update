<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Offer extends Model
{
    protected $fillable = [
        'product_name',
        'quantity',
        'sale_price',
        'delivery_charge',
        'total_amount',
        'status',
        'image',
        'public_id',
        'note',
        'sender_id',
        'receiver_id',
        'product_id',
        'color',
        'size'
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
