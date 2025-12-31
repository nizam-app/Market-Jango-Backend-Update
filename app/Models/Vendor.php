<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vendor extends Model
{
    protected $guarded = [];
    public function clickedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'vendor_clicks',
            'vendor_id',
            'user_id'
        )->withTimestamps();
    }
  public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_vendor');
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
