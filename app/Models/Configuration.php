<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory;

    protected $table = 'configurations';

    protected $fillable = [
        'key_name',
        'value',
    ];

    const DISCOUNT_POINTS_PERCENTAGE = 'discount_points_percentage';
    const RIYAL_TO_POINT = 'riyal_to_point';

    // Get the value of a default configuration
    public static function getDefaultConfiguration($keyName)
    {
        return self::where('key_name', $keyName)->value('value');
    }
}
