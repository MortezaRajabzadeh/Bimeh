<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'province_id', 
        'name', 
        'slug',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * استان مرتبط با این شهرستان
     */
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * دهستان‌های مرتبط با این شهرستان
     */
    public function districts()
    {
        return $this->hasMany(District::class);
    }
} 
