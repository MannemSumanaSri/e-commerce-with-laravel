<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <--- This line is essential for the trait
use App\Models\Cart; // Ensure these are imported if you've created these models
use App\Models\Order; // Ensure these are imported if you've created these models


class User extends Authenticatable
{
    // Make sure 'HasApiTokens' is listed here among the other traits
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // A user can have many carts (e.g., if they switch devices/sessions)
    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    // A user can have many orders
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

