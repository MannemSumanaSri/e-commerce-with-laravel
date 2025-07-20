<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id', // For guest carts
    ];

    // A cart belongs to a user (optional, for guest carts)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // A cart has many cart items
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    // Calculate total amount of the cart
    public function getTotalAmountAttribute()
    {
        return $this->items->sum(function($item) {
            return $item->quantity * $item->product->price;
        });
    }
}

