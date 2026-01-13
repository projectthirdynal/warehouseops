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
        Schema::create('waybill_tracking_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('waybill_id')->constrained('waybills')->cascadeOnDelete();
            $table->string('status', 50);
            $table->text('reason')->nullable();          // Reason for failed status
            $table->string('location', 255)->nullable(); // Hub/location where event occurred
            $table->timestamp('occurred_at')->nullable(); // When the event actually happened
            $table->timestamp('received_at')->useCurrent(); // When we received the webhook
            $table->json('raw_payload')->nullable();     // Full webhook payload for debugging
            $table->timestamps();

            $table->index(['waybill_id', 'occurred_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waybill_tracking_history');
    }
};
