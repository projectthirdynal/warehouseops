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
        // 1. Remove duplicates (keep latest)
        // PostgreSQL syntax to delete duplicates keeping the one with the highest ID
        DB::statement("
            DELETE FROM leads a 
            USING leads b 
            WHERE a.id < b.id AND a.phone = b.phone
        ");

        // 2. Add Unique Index
        Schema::table('leads', function (Blueprint $table) {
            $table->unique('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropUnique(['phone']);
        });
    }
};
