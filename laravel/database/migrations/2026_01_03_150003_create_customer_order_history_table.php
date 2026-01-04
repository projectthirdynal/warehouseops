<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the customer_order_history table for comprehensive order tracking.
     * This table aggregates data from waybills and leads for unified customer history.
     */
    public function up(): void
    {
        Schema::create('customer_order_history', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Link to customer
            $table->uuid('customer_id');
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('customers')
                  ->onDelete('cascade');

            // Order identification
            $table->string('waybill_number', 50);
            $table->string('source_type', 20)->default('UPLOAD'); // UPLOAD, LEAD_CONVERSION, REORDER, JNT_IMPORT

            // Product info
            $table->string('product_name', 255)->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('declared_value', 12, 2)->nullable();
            $table->decimal('cod_amount', 12, 2)->nullable();

            // Status tracking
            $table->string('current_status', 50)->default('PENDING');
            $table->jsonb('status_history')->default('[]'); // Array of status changes with timestamps

            // J&T specific fields (for manual import and future API)
            $table->string('jnt_waybill', 50)->nullable();
            $table->timestamp('jnt_last_sync')->nullable();
            $table->jsonb('jnt_raw_data')->nullable();

            // Lead info (if originated from lead)
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->string('lead_outcome', 50)->nullable();
            $table->string('lead_agent', 100)->nullable();

            // Waybill reference (if linked to waybill record)
            $table->unsignedBigInteger('waybill_id')->nullable();

            // Address at time of order
            $table->text('delivery_address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('barangay', 100)->nullable();

            // Key timestamps
            $table->timestamp('order_date')->nullable();
            $table->timestamp('shipped_date')->nullable();
            $table->timestamp('delivered_date')->nullable();
            $table->timestamp('returned_date')->nullable();

            $table->timestamps();

            // Unique constraint on waybill number
            $table->unique('waybill_number');

            // Indexes for common queries
            $table->index('customer_id');
            $table->index('current_status');
            $table->index('source_type');
            $table->index('order_date');
            $table->index('lead_id');
            $table->index('waybill_id');
            $table->index('jnt_waybill');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_order_history');
    }
};
