<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'family_id',
        'first_name',
        'last_name',
        'national_code',
        'father_name',
        'birth_date',
        'gender',
        'marital_status',
        'education',
        'occupation',
        'mobile',
        'relationship',
        'is_head',
        'has_disability',
        'has_chronic_disease',
        'has_insurance',
        'insurance_type',
    ];

    protected $casts = [
        'is_head' => 'boolean',
        'has_disability' => 'boolean',
        'has_chronic_disease' => 'boolean',
        'has_insurance' => 'boolean',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }
} 