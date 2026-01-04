<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Links leads to unified customer profiles.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Add customer_id as UUID foreign key (nullable for existing data)
            $table->uuid('customer_id')->nullable()->after('id');

            // Foreign key constraint
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('customers')
                  ->onDelete('set null');

            // Index for quick lookups
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropIndex(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};
