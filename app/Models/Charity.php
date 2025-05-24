<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Charity extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
     * فیلدهای قابل پر شدن
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'phone',
        'email',
        'website',
        'address',
        'is_active',
    ];
    
    /**
     * تبدیل فیلدها
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    /**
     * رابطه با جدول خانواده‌ها
     */
    public function families()
    {
        return $this->hasMany(Family::class);
    }
    
    /**
     * رابطه با جدول اعضای خانواده
     */
    public function members()
    {
        return $this->hasMany(Member::class);
    }
    
    /**
     * Scope برای خیریه‌های فعال
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
