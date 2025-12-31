<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class User extends Authenticatable
{
    use HasFactory, Notifiable;

     protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_active_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // -----------------------
    // Relations
    // -----------------------
     public function clickedVendors(): BelongsToMany
    {
        return $this->belongsToMany(
            Vendor::class,
            'vendor_clicks',
            'user_id',
            'vendor_id'
        )->withTimestamps();
    }
    public function vendor()
    {
        return $this->hasOne(Vendor::class);
    }

    public function buyer()
    {
        return $this->hasOne(Buyer::class);
    }

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function transport()
    {
        return $this->hasOne(Transport::class);
    }
    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    public function reviews()
    {
        return $this->hasOne(Review::class);
    }
    public function item()
    {
        return $this->hasOne(InvoiceItem::class);
    }

    public function sentNotifications()
    {
        return $this->hasMany(Notification::class, 'sender_id');
    }

    public function receivedNotifications()
    {
        return $this->hasMany(Notification::class, 'receiver_id');
    }
    public function clicks()
    {
        return $this->hasMany(ProductClickLog::class, 'user_id');
    }


    // -----------------------
    // Dynamic Attributes
    // -----------------------
    public function getIsOnlineAttribute()
    {
        return $this->last_active_at
            && $this->last_active_at->gt(now()->subMinutes(2));
    }

    public function getLastSeenAttribute()
    {
        return $this->last_active_at
            ? $this->last_active_at->diffForHumans()
            : 'Never';
    }

    // -----------------------
    // Manual Role-Permission Relations
    // -----------------------
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }
//    public function roles()
//    {
//        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id');
//    }

    /**
     * Get all permissions of the user via roles
     */
    public function permissions()
    {
        return $this->roles()->with('permissions')->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission($permissionName)
    {
        return $this->permissions()->contains('name', $permissionName);
    }

    /**
     * Assign a role manually to user
     */
    public function assignRole($roleId)
    {
        $this->roles()->syncWithoutDetaching([$roleId]);
    }
}