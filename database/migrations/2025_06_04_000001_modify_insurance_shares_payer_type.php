<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the column needs modification
        $columnType = DB::select("SHOW COLUMNS FROM insurance_shares WHERE Field = 'payer_type'");
        
        if (!empty($columnType) && strpos($columnType[0]->Type, 'enum') !== false) {
            // Step 1: Add new string column
            Schema::table('insurance_shares', function (Blueprint $table) {
                $table->string('payer_type_slug')->after('payer_type')->nullable();
            });
            
            // Step 2: Copy existing data
            DB::statement("UPDATE insurance_shares SET payer_type_slug = payer_type");
            
            // Step 3: Drop the old enum column
            Schema::table('insurance_shares', function (Blueprint $table) {
                $table->dropColumn('payer_type');
            });
            
            // Step 4: Rename the new column
            Schema::table('insurance_shares', function (Blueprint $table) {
                $table->renameColumn('payer_type_slug', 'payer_type');
            });
            
            // Step 5: Add index
            Schema::table('insurance_shares', function (Blueprint $table) {
                $table->index('payer_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_shares', function (Blueprint $table) {
            // First, create a new column
            $table->enum('payer_type_enum', [
                'insurance_company', 
                'charity', 
                'bank', 
                'government', 
                'individual_donor',
                'csr_budget',
                'other'
            ])->after('payer_type')->nullable();
            
            // Copy existing data
            DB::statement("UPDATE insurance_shares SET payer_type_enum = payer_type");
            
            // Drop the old string column
            $table->dropColumn('payer_type');
            
            // Rename the new column
            $table->renameColumn('payer_type_enum', 'payer_type');
        });
    }
}; 