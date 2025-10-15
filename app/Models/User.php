<?php
namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
/** @use HasFactory<\Database\Factories\UserFactory> */
use HasFactory, Notifiable;

/**
* The attributes that are mass assignable.
*
* @var list<string>
    */
    protected $fillable = [
    'name',
    'email',
    'password',
    'phone',
    'otp',
    'token',
    'user_type',
    'expires_at',
    'phone_verified_at',
    'status'
    ];

    /**
    * The attributes that should be hidden for serialization.
    *
    * @var list<string>
        */
        protected $hidden = [
        'password',
        'remember_token',
        ];

        /**
        * Get the attributes that should be cast.
        *
        * @return array<string, string>
        */
        protected $casts = [

        ];
        protected function casts(): array
        {
        return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'expires_at' => 'datetime',
        ];
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
        }


