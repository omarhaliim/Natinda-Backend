<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Cart extends Model
{
    use HasFactory;

    protected $table = 'carts';

    protected $fillable = [
        'user_id',
        'guest_id',
        'shipping_address_id',
        'subtotal',
        'shipping_fees',
        'tax',
        'promocode_id',
        'promocode_price',
        'points',
        'points_price',
        'total_price',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shippingAddress()
    {
        return $this->belongsTo(ShippingAddress::class, 'shipping_address_id');
    }

    public function promocode()
    {
        return $this->belongsTo(PromoCode::class, 'promocode_id');
    }



    public function cartProducts()
    {
        return $this->hasMany(CartProduct::class);
    }



    public function products()
    {
        // Define a many-to-many relationship with the Product model.
        // Uses the 'cart_products' pivot table.
        // 'cart_id' is the foreign key in the pivot table linking to the Cart model.
        // 'product_id' is the foreign key in the pivot table linking to the Product model.
        return $this->belongsToMany(Product::class, 'cart_products', 'cart_id', 'product_id');
    }
}
