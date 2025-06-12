<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class StatsRepository
{
    public function getFamilyStatsForCharity($charityId)
    {
        return DB::select("
            SELECT 
                SUM(CASE WHEN f.is_insured = 1 THEN 1 ELSE 0 END) as insured_families,
                SUM(CASE WHEN f.is_insured = 0 THEN 1 ELSE 0 END) as uninsured_families,
                COUNT(DISTINCT CASE WHEN f.is_insured = 1 THEN m.id END) as insured_members,
                COUNT(DISTINCT CASE WHEN f.is_insured = 0 THEN m.id END) as uninsured_members
            FROM families f
            LEFT JOIN members m ON m.family_id = f.id
            WHERE f.charity_id = ?
        ", [$charityId]);
    }
}