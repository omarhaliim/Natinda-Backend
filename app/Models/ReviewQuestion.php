<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewQuestion extends Model
{
    protected $table = 'review_questions';

    protected $fillable = [
        'question_en',
        'question_ar',
        'review_id',
        'type',
    ];

    public function review()
    {
        return $this->belongsTo(Review::class, 'review_id', 'id');
    }

    public function answers()
    {
        return $this->hasMany(ReviewAnswer::class, 'question_id');
    }
}
