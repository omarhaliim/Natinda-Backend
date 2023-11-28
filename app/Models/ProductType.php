<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductType extends Model
{
    use HasFactory;
    protected $table = "products_type";

    protected $fillable = [
        'name_en',
        'name_ar',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'type_id');
    }


}
