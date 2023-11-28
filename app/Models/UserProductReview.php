<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserProductReview extends Pivot
{
    protected $table = 'user_product_reviews';

    protected $fillable = [
        'user_id',
        'product_id'
    ];

    public $timestamps = false;


    // You can add any additional attributes to this pivot table if needed

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
