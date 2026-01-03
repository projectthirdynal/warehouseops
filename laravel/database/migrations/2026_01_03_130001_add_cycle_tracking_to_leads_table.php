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
            // Cycle tracking fields
            $table->integer('total_cycles')->default(0)->after('call_attempts');
            $table->integer('max_cycles')->default(3)->after('total_cycles');
            $table->boolean('is_exhausted')->default(false)->after('max_cycles');
            
            // Index for filtering exhausted leads
            $table->index('is_exhausted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['is_exhausted']);
            $table->dropColumn(['total_cycles', 'max_cycles', 'is_exhausted']);
        });
    }
};
