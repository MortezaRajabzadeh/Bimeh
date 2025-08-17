<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the unique constraint already exists
        $hasUniqueConstraint = \DB::select(
            "SELECT COUNT(*) as count FROM information_schema.STATISTICS 
             WHERE table_schema = DATABASE() 
               AND table_name = 'members' 
               AND index_name = 'members_national_code_unique'"
        );
        
        if ($hasUniqueConstraint[0]->count == 0) {
            Schema::table('members', function (Blueprint $table) {
                $table->unique('national_code', 'members_national_code_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique('members_national_code_unique');
        });
    }
};
