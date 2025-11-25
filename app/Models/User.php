<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'user_type',
        'name',
        'email',
        'phone',
        'otp',
        'phone_verified_at',
        'password',
        'language',
        'image',
        'public_id',
        'is_read',
        'is_active',
        'is_online',
        'status',
        'expires_at'
    ];

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

    public function sentNotifications()
    {
        return $this->hasMany(Notification::class, 'sender_id');
    }

    public function receivedNotifications()
    {
        return $this->hasMany(Notification::class, 'receiver_id');
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



//namespace App\Models;
//
//// use Illuminate\Contracts\Auth\MustVerifyEmail;
//use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Foundation\Auth\User as Authenticatable;
//use Illuminate\Notifications\Notifiable;
//
//class User extends Authenticatable
//{
///** @use HasFactory<\Database\Factories\UserFactory> */
//use HasFactory, Notifiable;
//
///**
//* The attributes that are mass assignable.
//*
//* @var list<string>
//    */
//    protected $fillable = [
//    'user_type',
//    'name',
//    'email',
//    'phone',
//    'otp',
//    'phone_verified_at',
//    'password',
//    'language',
//    'image',
//    'public_id',
//    'is_read',
//    'is_active',
//    'status',
//    'expires_at'
//    ];
//
//    /**
//    * The attributes that should be hidden for serialization.
//    *
//    * @var list<string>
//        */
//        protected $hidden = [
//        'password',
//        'remember_token',
//        ];
//
//        /**
//        * Get the attributes that should be cast.
//        *
//        * @return array<string, string>
//        */
//        protected $casts = [
//            'expires_at' => 'datetime', 'last_active_at' => 'datetime',
//        ];
//        protected function casts(): array
//        {
//        return [
//        'email_verified_at' => 'datetime',
//        'password' => 'hashed',
//        ];
//        }
//        public function vendor()
//        {
//        return $this->hasOne(Vendor::class);
//        }
//        public function buyer()
//        {
//        return $this->hasOne(Buyer::class);
//        }
//        public function driver()
//        {
//        return $this->hasOne(Driver::class);
//        }
//        public function transport()
//        {
//            return $this->hasOne(Transport::class);
//        }
//        public function reviews()
//        {
//            return $this->hasOne(Review::class);
//        }
//    // Notifications sent by this user
//    public function sentNotifications()
//    {
//        return $this->hasMany(Notification::class, 'sender_id');
//    }
//
//// Notifications received by this user
//    public function receivedNotifications()
//    {
//        return $this->hasMany(Notification::class, 'receiver_id');
//    }
//    /**
//     * Dynamic online status
//     */
//    public function getIsOnlineAttribute()
//    {
//        return $this->last_active_at
//            && $this->last_active_at->gt(now()->subMinutes(2));
//    }
//    /**
//     * Friendly last seen
//     */
//    public function getLastSeenAttribute()
//    {
//        return $this->last_active_at
//            ? $this->last_active_at->diffForHumans()
//            : 'Never';
//    }
//
//}
//
//
