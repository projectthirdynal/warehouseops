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
        Schema::table('leads', function (Blueprint $table) {
            // Drop the existing unique index if it exists
            // Since we added it in a previous session, we'll try to drop it.
            // PostgreSQL often names it leads_phone_unique
            $table->dropUnique(['phone']);
            
            // Add new columns
            $table->string('source')->default('fresh')->index();
            $table->foreignId('original_agent_id')->nullable()->constrained('users')->onDelete('set null');
        });

        // Add Partial Unique Index (PostgreSQL only)
        // One active lead per phone number. Active means NOT SALE, DELIVERED, or CANCELLED.
        DB::statement("
            CREATE UNIQUE INDEX leads_phone_active_unique ON leads (phone) 
            WHERE status NOT IN ('SALE', 'DELIVERED', 'CANCELLED')
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop partial index
        DB::statement("DROP INDEX IF EXISTS leads_phone_active_unique");

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['source', 'original_agent_id']);
            $table->unique('phone');
        });
    }
};
