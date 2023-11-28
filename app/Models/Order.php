<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'shipping_address_id',
        'subtotal',
        'shipping_fees',
        'tax',
        'promocode_id',
        'promocode_price',
        'points',
        'points_price',
        'total_price',
        'is_paid',
        'status',
    ];

    protected $statuses = [
        '1' => 'Pending',
        '2' => 'Approved',
        '3' => 'Shipped',
        '4' => 'Received',
        '5' => 'Rejected',
        '6' => 'Returned Damaged', // dont add to stock
        '7' => 'Returned', // add to stock


    ];

    public function getStatusAttribute($value)
    {
        return $this->statuses[$value] ?? 'Unknown';
    }

    public static function getStatusOptions()
    {
        return [
            '1' => 'Pending',
            '2' => 'Approved',
            '3' => 'Shipped',
            '4' => 'Received',
            '5' => 'Rejected',
            '6' => 'Returned Damaged',
            '7' => 'Returned',
        ];
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }



    public function shippingaddress()
    {
        return $this->belongsTo(ShippingAddress::class, 'shipping_address_id');
    }

    public function promocode()
    {
        return $this->belongsTo(PromoCode::class, 'promocode_id');
    }


}
