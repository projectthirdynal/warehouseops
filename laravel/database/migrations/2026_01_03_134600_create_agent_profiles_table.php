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
        Schema::create('agent_profiles', function (Blueprint $table) {
            $table->id();
            
            // Link to user (one-to-one)
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            
            // Capacity management
            $table->integer('max_active_cycles')->default(10);
            
            // Skill matching - JSON array of product names/brands the agent specializes in
            $table->json('product_skills')->nullable();
            
            // Region coverage - JSON array of states/cities the agent covers
            $table->json('regions')->nullable();
            
            // Priority weight for manual boosting (1.0 = normal, >1 = boosted, <1 = deprioritized)
            $table->decimal('priority_weight', 3, 2)->default(1.00);
            
            // Availability toggle (for temporary pause without deactivating account)
            $table->boolean('is_available')->default(true);
            
            // Performance metrics (updated periodically)
            $table->decimal('conversion_rate', 5, 2)->nullable(); // % of cycles ending in sale
            $table->integer('avg_calls_per_cycle')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('is_available');
            $table->index('priority_weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_profiles');
    }
};
