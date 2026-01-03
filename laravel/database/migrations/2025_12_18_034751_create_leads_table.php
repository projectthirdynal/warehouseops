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
        // 1. Leads Table
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            
            // Contact Info
            $table->string('name');
            $table->string('phone')->index(); // Indexed for checking duplicates vs faster lookups
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            
            // Status Tracking
            // Enum: NEW, CALLING, NO_ANSWER, REJECT, CALLBACK, NOT_INTERESTED, SALE, DELIVERED, CANCELLED
            $table->string('status')->default('NEW')->index(); 
            
            // Assignment
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Metadata
            $table->timestamp('last_called_at')->nullable();
            $table->integer('call_attempts')->default(0);
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });

        // 2. Lead Logs Table (History)
        Schema::create('lead_logs', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained(); // Agent who performed action
            
            $table->string('action'); // 'status_change', 'note', 'call'
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->text('description')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_logs');
        Schema::dropIfExists('leads');
    }
};
