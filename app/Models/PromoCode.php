<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $table = 'promocodes';

    protected $fillable = [
        'code',
        'value',
        'is_working',
        'max_number_of_used',
    ];

    protected $is_working = [
        '1' => 'Working',
        '2' => 'Expired',
    ];

    public function getis_workingAttribute($value)
    {
        return $this->is_working[$value] ?? 'Unknown';
    }



}
