<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RankingScheme extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'description', 'user_id'];

    /**
     * The criteria that belong to the ranking scheme.
     */
    public function criteria()
    {
        return $this->belongsToMany(RankSetting::class, 'ranking_scheme_criteria')
                    ->withPivot('weight') // مهم: برای دسترسی به وزن در جدول واسط
                    ->withTimestamps();
    }
}