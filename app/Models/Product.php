<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Product extends Model
{
    use HasFactory;
    protected $table = "products";

    protected $fillable = [
        'name_en',
        'name_ar',
        'price',
        'quantity',
        'discounted_price',
        'description_en',
        'description_ar',
        'how_to_use_en',
        'how_to_use_ar',
        'ingredients_en',
        'ingredients_ar',
        'type_id',
        'status'
    ];

    protected $statuses = [
        '1' => 'Live', // On website
        '2' => 'Sold out', // On webite put not avaliable to buy
        '3' => 'Hidden', // Not on Website
    ];

    public function getStatusAttribute($value)
    {
        return $this->statuses[$value] ?? 'Unknown';
    }


    public function productType()
    {
        return $this->belongsTo(ProductType::class, 'type_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }


    public function reviews()
    {
        return $this->hasMany(Review::class, 'product_id');
    }

    public function faqs()
    {
        return $this->hasMany(FAQ::class, 'product_id');
    }


    public function userproductreviews()
    {
        return $this->hasMany(UserProductReview::class, 'product_id');
    }

    public function carts()
    {
        // Define the inverse of the many-to-many relationship.
        // A product can be associated with multiple carts.
        // Uses the same 'cart_products' pivot table.
        // 'product_id' is the foreign key in the pivot table linking to the Product model.
        // 'cart_id' is the foreign key in the pivot table linking back to the Cart model.
        return $this->belongsToMany(Cart::class, 'cart_products', 'product_id', 'cart_id');
    }
}
