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
        Schema::table('waybills', function (Blueprint $table) {
            // Link waybill to lead (for recycling validation)
            $table->foreignId('lead_id')->nullable()->after('upload_id')->constrained()->onDelete('set null');
            
            // Index for quick lookups
            $table->index('lead_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waybills', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
            $table->dropIndex(['lead_id']);
            $table->dropColumn('lead_id');
        });
    }
};
