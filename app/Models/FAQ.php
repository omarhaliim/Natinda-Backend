<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FAQ extends Model
{
    protected $table = 'faq';

    protected $fillable = [
        'product_id',
        'question_en',
        'question_ar',
        'answer_en',
        'answer_ar',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
