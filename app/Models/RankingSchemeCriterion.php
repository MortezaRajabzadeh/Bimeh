<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RankingSchemeCriterion extends Model
{
    use HasFactory;
    
    protected $table = 'ranking_scheme_criteria';
    protected $fillable = ['ranking_scheme_id', 'rank_setting_id', 'weight'];
}
