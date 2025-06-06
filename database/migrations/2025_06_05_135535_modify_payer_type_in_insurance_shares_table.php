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
        // First check if the column exists
        if (Schema::hasColumn('insurance_shares', 'payer_type')) {
            // Create a payer_types table if it doesn't exist
            if (!Schema::hasTable('payer_types')) {
                Schema::create('payer_types', function (Blueprint $table) {
                    $table->id();
                    $table->string('name');
                    $table->string('code')->unique();
                    $table->text('description')->nullable();
                    $table->boolean('is_active')->default(true);
                    $table->timestamps();
                });
                
                // Insert the existing enum values as records in the payer_types table
                $payerTypes = [
                    ['name' => 'شرکت بیمه', 'code' => 'insurance_company'],
                    ['name' => 'خیریه', 'code' => 'charity'],
                    ['name' => 'بانک', 'code' => 'bank'],
                    ['name' => 'دولتی', 'code' => 'government'],
                    ['name' => 'حامی مالی شخصی', 'code' => 'individual_donor'],
                    ['name' => 'بودجه مسئولیت اجتماعی', 'code' => 'csr_budget'],
                    ['name' => 'سایر', 'code' => 'other'],
                ];
                
                foreach ($payerTypes as $type) {
                    DB::table('payer_types')->insert([
                        'name' => $type['name'],
                        'code' => $type['code'],
                        'description' => null,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            
            // Add a temporary column to store the current payer_type
            Schema::table('insurance_shares', function (Blueprint $table) {
                $table->unsignedBigInteger('payer_type_id')->nullable();
            });
            
            // Update the payer_type_id based on the existing payer_type enum values
            DB::statement("
                UPDATE insurance_shares
                SET payer_type_id = (
                    SELECT id FROM payer_types
                    WHERE code = insurance_shares.payer_type
                )
            ");
            
            // Now drop the enum column and add the foreign key
            Schema::table('insurance_shares', function (Blueprint $table) {
                // Make sure we have a relationship with payer_type_id
                $table->foreign('payer_type_id')
                      ->references('id')
                      ->on('payer_types')
                      ->onDelete('set null');
                      
                // Now we can drop the old column safely
                $table->dropColumn('payer_type');
            });
            
            // Rename payer_type_id to payer_type_id (keep the same name for backward compatibility)
            Schema::table('insurance_shares', function (Blueprint $table) {
                $table->renameColumn('payer_type_id', 'payer_type_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('insurance_shares', 'payer_type_id')) {
            // First add back the enum column
            Schema::table('insurance_shares', function (Blueprint $table) {
                $table->enum('payer_type', [
                    'insurance_company',
                    'charity',
                    'bank',
                    'government',
                    'individual_donor',
                    'csr_budget',
                    'other'
                ])->nullable();
            });
            
            // Restore values from payer_type_id to payer_type
            DB::statement("
                UPDATE insurance_shares
                SET payer_type = (
                    SELECT code FROM payer_types
                    WHERE id = insurance_shares.payer_type_id
                )
            ");
            
            // Drop the foreign key and payer_type_id column
            Schema::table('insurance_shares', function (Blueprint $table) {
                $table->dropForeign(['payer_type_id']);
                $table->dropColumn('payer_type_id');
            });
        }
    }
};
