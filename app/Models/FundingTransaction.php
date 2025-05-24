<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FundingTransaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'funding_source_id', 'amount', 'description', 'reference_no', 'allocated'
    ];

    public function source()
    {
        return $this->belongsTo(FundingSource::class, 'funding_source_id');
    }
} 