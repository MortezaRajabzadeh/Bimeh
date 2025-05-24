<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FamilyInsurance extends Model
{
    protected $fillable = [
        'family_id', 
        'insurance_type', 
        'insurance_amount', 
        'insurance_issue_date', 
        'insurance_end_date', 
        'insurance_payer'
    ];

    protected $casts = [
        'insurance_issue_date' => 'date',
        'insurance_end_date' => 'date',
        'insurance_amount' => 'decimal:2',
    ];

    public function family()
    {
        return $this->belongsTo(Family::class);
    }
} 