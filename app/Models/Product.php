<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'regular_price',
        'sell_price',
        'discount',
        'public_id',
        'star',
        'image',
        'color',
        'size',
        'remark',
        'is_active',
        'vendor_id',
        'new_item',
        'just_for_you',
        'top_product',
        'category_id'
    ];
    protected $casts = [
        'color' => 'array',
        'size' => 'array',
//        'new_item' => 'boolean',
//        'just_for_you' => 'boolean',
//        'top_product' => 'boolean'
    ];
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function images(){
        return $this->hasMany(ProductImage::class);
    }
    public function banner(){
        return $this->hasOne(ProductBanner::class);
    }
    public function variants()
    {
        return $this->hasMany(ProductAttribute::class);
    }
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }
    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'product_id', 'id');
    }
    public function clicks()
    {
        return $this->hasMany(ProductClickLog::class, 'product_id');
    }

    public function scopeBestSellersByVendorFromInvoice(
        Builder $query,
        int $vendorId,
        ?int $days = null,
        int $limit = 30
    ): Builder {
        // তোমার ইনভয়েসের সফল স্ট্যাটাসগুলো
        $completedPaymentStatuses = ['Paid','Completed','Shipped']; // প্রয়োজনে কাস্টমাইজ

        return $query
            ->whereHas('invoiceItems', function ($q) use ($vendorId, $completedPaymentStatuses, $days) {
                $q->where('vendor_id', $vendorId)
                    ->whereHas('invoice', function ($iq) use ($completedPaymentStatuses, $days) {
                        $iq->whereIn('payment_status', $completedPaymentStatuses);
                        if ($days) {
                            $iq->where('created_at', '>=', now()->subDays($days));
                        }
                    });
            })
            ->withSum(['invoiceItems as sold_qty' => function ($q) use ($vendorId, $completedPaymentStatuses, $days) {
                $q->where('vendor_id', $vendorId)
                    ->whereHas('invoice', function ($iq) use ($completedPaymentStatuses, $days) {
                        $iq->whereIn('payment_status', $completedPaymentStatuses);
                        if ($days) {
                            $iq->where('created_at', '>=', now()->subDays($days));
                        }
                    });
            }], 'quantity')
            ->orderByDesc('sold_qty')
            ->limit($limit);
    }
}
