<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id', 
        'name', 
        'slug',
        'is_active',
        'is_deprived'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deprived' => 'boolean',
    ];

    /**
     * شهرستان مرتبط با این دهستان
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
