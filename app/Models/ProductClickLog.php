<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductClickLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'product_id',
        'user_id',
        'ip',
        'device',
    ];

    // ---------------- Relations ----------------

    // Vendor relation
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    // Product relation
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // User relation (optional)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
