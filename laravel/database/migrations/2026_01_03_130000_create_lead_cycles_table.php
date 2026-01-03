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
        Schema::create('lead_cycles', function (Blueprint $table) {
            $table->id();
            
            // Core relationships
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');
            
            // Cycle tracking
            $table->integer('cycle_number')->default(1);
            $table->string('status')->default('ACTIVE'); // ACTIVE, CLOSED_SALE, CLOSED_REJECT, CLOSED_RETURNED, CLOSED_EXHAUSTED
            
            // Timestamps
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            
            // Call tracking (per cycle)
            $table->integer('call_attempts')->default(0);
            $table->timestamp('last_called_at')->nullable();
            
            // Structured notes (JSON array of log entries)
            $table->json('notes')->nullable();
            
            // Waybill binding (optional link to waybill created during this cycle)
            $table->foreignId('waybill_id')->nullable()->constrained()->onDelete('set null');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['lead_id', 'status']);
            $table->index(['agent_id', 'status']);
            $table->index('opened_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_cycles');
    }
};
