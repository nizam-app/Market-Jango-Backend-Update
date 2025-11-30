<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'country',
        'address',
        'latitude',
        'longitude',
        'business_name',
        'business_type',
        'open_time',
        'close_time',
        'user_id'
    ];
    public function categories()
    {
        return $this->hasMany(Category::class, 'vendor_id', 'id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function products()
    {
        return $this->hasMany(Product::class, 'vendor_id', 'id');
    }
    public function images(){
        return $this->hasMany(UserImage::class,'user_id');
    }
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
    public function clicks()
    {
        return $this->hasMany(ProductClickLog::class, 'vendor_id');
    }


}
