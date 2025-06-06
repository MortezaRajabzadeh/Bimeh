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
        if (!Schema::hasTable('insurance_import_logs')) {
            Schema::create('insurance_import_logs', function (Blueprint $table) {
                $table->id();
                $table->string('file_name');
                $table->json('row_data')->nullable();
                $table->string('status')->default('pending');
                $table->text('message')->nullable();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('family_id')->nullable()->constrained()->onDelete('set null');
                $table->integer('total_rows')->default(0);
                $table->integer('created_count')->default(0);
                $table->integer('updated_count')->default(0);
                $table->integer('skipped_count')->default(0);
                $table->integer('error_count')->default(0);
                $table->decimal('total_insurance_amount', 15, 2)->default(0);
                $table->json('family_codes')->nullable();
                $table->json('updated_family_codes')->nullable();
                $table->json('created_family_codes')->nullable();
                $table->text('errors')->nullable();
                $table->timestamps();
            });
        } else {
            Schema::table('insurance_import_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('insurance_import_logs', 'file_name')) {
                    $table->string('file_name')->after('id');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'row_data')) {
                    $table->json('row_data')->nullable()->after('file_name');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'status')) {
                    $table->string('status')->default('pending')->after('row_data');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'message')) {
                    $table->text('message')->nullable()->after('status');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'family_id')) {
                    $table->foreignId('family_id')->nullable()->constrained()->onDelete('set null');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'total_rows')) {
                    $table->integer('total_rows')->default(0);
                }
                if (!Schema::hasColumn('insurance_import_logs', 'created_count')) {
                    $table->integer('created_count')->default(0);
                }
                if (!Schema::hasColumn('insurance_import_logs', 'updated_count')) {
                    $table->integer('updated_count')->default(0);
                }
                if (!Schema::hasColumn('insurance_import_logs', 'skipped_count')) {
                    $table->integer('skipped_count')->default(0);
                }
                if (!Schema::hasColumn('insurance_import_logs', 'error_count')) {
                    $table->integer('error_count')->default(0);
                }
                if (!Schema::hasColumn('insurance_import_logs', 'total_insurance_amount')) {
                    $table->decimal('total_insurance_amount', 15, 2)->default(0);
                }
                if (!Schema::hasColumn('insurance_import_logs', 'family_codes')) {
                    $table->json('family_codes')->nullable();
                }
                if (!Schema::hasColumn('insurance_import_logs', 'updated_family_codes')) {
                    $table->json('updated_family_codes')->nullable();
                }
                if (!Schema::hasColumn('insurance_import_logs', 'created_family_codes')) {
                    $table->json('created_family_codes')->nullable();
                }
                if (!Schema::hasColumn('insurance_import_logs', 'errors')) {
                    $table->text('errors')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We won't drop the table or columns in the down method
        // as it would lead to data loss
    }
};
