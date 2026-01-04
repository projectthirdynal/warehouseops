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
        Schema::create('lead_recycling_pool', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Link to customer (cascade delete when customer is deleted)
            $table->uuid('customer_id');
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');

            // Source tracking
            $table->string('source_waybill', 50)->nullable();
            $table->uuid('source_lead_id')->nullable();
            $table->string('original_outcome', 50)->nullable(); // What happened last time

            // Recycling metadata
            $table->string('recycle_reason', 100)->nullable(); // Why it's being recycled
            $table->integer('recycle_count')->default(1); // Number of times recycled
            $table->integer('priority_score')->default(50); // 0-100, higher = contact first

            // Scheduling
            $table->timestamp('available_from')->default(now());
            $table->timestamp('expires_at')->nullable();

            // Assignment
            $table->unsignedBigInteger('assigned_to')->nullable(); // agent user_id
            $table->timestamp('assigned_at')->nullable();

            // Status tracking
            $table->string('pool_status', 20)->default('AVAILABLE'); // AVAILABLE, ASSIGNED, CONVERTED, EXPIRED, EXHAUSTED

            // Outcome when processed
            $table->timestamp('processed_at')->nullable();
            $table->string('processed_outcome', 50)->nullable();

            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['pool_status', 'available_from']); // Main query index
            $table->index('customer_id'); // Customer lookup
            $table->index(['priority_score']); // Priority ordering
            $table->index(['assigned_to', 'pool_status']); // Agent assignment lookup
            $table->index('expires_at'); // Expiration cleanup

            // Foreign key for agent assignment
            $table->foreign('assigned_to')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_recycling_pool');
    }
};
