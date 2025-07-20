<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CartItem;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name_en',
        'name_hi',
        'description_en',
        'description_hi',
        'price',
        'image_url',
        'stock',
        'is_featured',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
    ];

    // A product belongs to a category
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // A product can be in many cart items
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    // A product can be in many order items
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
