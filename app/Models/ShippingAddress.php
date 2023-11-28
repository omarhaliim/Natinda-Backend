<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    protected $table = 'shipping_address';

    protected $fillable = [
        'user_id',
        'default_user_flag',
        'first_name',
        'last_name',
        'address',
        'appartment',
        'district',
        'region',
        'phone',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'order_id');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class, 'cart_id');
    }


}
