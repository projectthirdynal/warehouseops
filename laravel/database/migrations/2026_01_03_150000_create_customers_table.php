<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the customers table for unified customer profiles.
     * This is the foundation of the Lead Recycling & Customer Historical Tracking System.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            // Primary key - using UUID for distributed systems compatibility
            $table->uuid('id')->primary();

            // Identity (for matching/deduplication)
            $table->string('phone_primary', 20);
            $table->string('phone_secondary', 20)->nullable();
            $table->string('name_normalized', 255)->nullable();
            $table->string('name_display', 255)->nullable();

            // Location data (aggregated from orders)
            $table->text('primary_address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('barangay', 100)->nullable();
            $table->string('street', 255)->nullable();

            // Performance Metrics (denormalized for fast access)
            $table->integer('total_orders')->default(0);
            $table->integer('total_delivered')->default(0);
            $table->integer('total_returned')->default(0);
            $table->integer('total_pending')->default(0);
            $table->integer('total_in_transit')->default(0);

            // Financial metrics
            $table->decimal('total_order_value', 12, 2)->default(0);
            $table->decimal('total_delivered_value', 12, 2)->default(0);
            $table->decimal('total_returned_value', 12, 2)->default(0);

            // Computed scores
            $table->decimal('delivery_success_rate', 5, 2)->default(0);
            $table->integer('customer_score')->default(50);
            $table->string('risk_level', 20)->default('UNKNOWN');

            // Lead recycling metadata
            $table->integer('times_contacted')->default(0);
            $table->timestamp('last_contact_date')->nullable();
            $table->timestamp('last_order_date')->nullable();
            $table->timestamp('last_delivery_date')->nullable();
            $table->boolean('recycling_eligible')->default(true);
            $table->timestamp('recycling_cooldown_until')->nullable();

            // Timestamps
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamps();

            // Indexes
            $table->unique('phone_primary');
            $table->index('phone_secondary');
            $table->index('name_normalized');
            $table->index('risk_level');
            $table->index('customer_score', 'idx_customers_score');
            $table->index(['recycling_eligible', 'recycling_cooldown_until'], 'idx_customers_recycling');
            $table->index('city');
            $table->index('province');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
