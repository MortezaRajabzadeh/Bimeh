<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsuranceAllocation extends Model
{
    use HasFactory;
    protected $fillable = [
        'funding_transaction_id', 'family_id', 'amount', 'issue_date', 'paid_at', 'description'
    ];

    public function transaction()
    {
        return $this->belongsTo(FundingTransaction::class, 'funding_transaction_id');
    }

    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }
} 