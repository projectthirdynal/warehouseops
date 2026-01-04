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
        Schema::table('leads', function (Blueprint $table) {
            // Track which recycling pool entry this lead came from (if any)
            $table->uuid('recycling_pool_id')->nullable()->after('customer_id');

            // Foreign key to recycling pool (set null on delete)
            $table->foreign('recycling_pool_id')
                ->references('id')
                ->on('lead_recycling_pool')
                ->onDelete('set null');

            // Index for querying leads from recycling pool
            $table->index('recycling_pool_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['recycling_pool_id']);
            $table->dropColumn('recycling_pool_id');
        });
    }
};
