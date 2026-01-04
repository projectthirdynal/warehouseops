<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Call logs for VoIP softphone integration - tracks all calls made by agents.
     */
    public function up(): void
    {
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Agent
            $table->foreignId('lead_id')->nullable()->constrained()->onDelete('set null');
            $table->string('phone_number', 20);
            $table->string('call_id', 64)->unique(); // SIP Call-ID
            $table->enum('direction', ['outbound', 'inbound'])->default('outbound');
            $table->enum('status', ['initiated', 'ringing', 'answered', 'ended', 'failed', 'missed', 'busy'])->default('initiated');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->string('recording_url')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // SIP headers, codec info, etc.
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['lead_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('phone_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};
