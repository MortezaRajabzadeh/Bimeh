<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'deprivation_rank',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deprivation_rank' => 'integer',
    ];

    /**
     * شهرستان‌های مرتبط با این استان
     */
    public function cities()
    {
        return $this->hasMany(City::class);
    }

    /**
     * خانواده‌های مرتبط با این استان
     */
    public function families()
    {
        return $this->hasMany(\App\Models\Family::class, 'province_id');
    }
} 