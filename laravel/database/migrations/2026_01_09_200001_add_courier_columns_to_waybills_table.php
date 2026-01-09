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
            // Courier provider reference
            $table->foreignId('courier_provider_id')->nullable()->constrained('courier_providers')->nullOnDelete();
            
            // Courier-specific tracking fields
            $table->string('courier_waybill_no', 50)->nullable()->index(); // J&T's waybill number
            $table->string('courier_sorting_code', 50)->nullable();        // Sorting code from API
            $table->string('courier_tracking_status', 50)->nullable();     // Latest status from courier
            $table->text('courier_status_reason')->nullable();             // Reason for failed delivery/pickup
            $table->timestamp('courier_last_update')->nullable();          // Last status update time
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waybills', function (Blueprint $table) {
            $table->dropForeign(['courier_provider_id']);
            $table->dropColumn([
                'courier_provider_id',
                'courier_waybill_no',
                'courier_sorting_code',
                'courier_tracking_status',
                'courier_status_reason',
                'courier_last_update',
            ]);
        });
    }
};
