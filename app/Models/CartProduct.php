<?php

namespace App\Models;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class CartProduct extends Model
{
    //protected $primaryKey = ['cart_id', 'product_id'];

    protected $primaryKey = null; // Disable Eloquent's primary key behavior for this model
    public $incrementing = false; // Disable auto-incrementing primary keys


    protected $table = 'cart_products';

//protected $primaryKey = null; // Since it's a composite primary key, set to null
// In your CartProduct model


    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        // Add other attributes as needed
    ];

    // Rest of your model code...



    public $timestamps = false; // To disable timestamp columns

    // Define the relationship with the Cart model
    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id', 'id');
    }

    // Define the relationship with the Product model
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
