<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->json('problem_type')->nullable()->after('occupation');
        });
    }
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn('problem_type');
        });
    }
}; 