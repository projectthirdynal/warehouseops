<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SIP accounts for VoIP softphone - can be global or per-agent.
     */
    public function up(): void
    {
        Schema::create('sip_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // null = global account
            $table->string('name', 100)->default('Default'); // Display name for this config
            $table->string('sip_server', 255); // e.g., "sip.twilio.com"
            $table->string('ws_server', 255);  // e.g., "wss://sip.twilio.com:443"
            $table->string('username', 100);
            $table->text('password'); // Encrypted in model
            $table->string('display_name', 100)->nullable();
            $table->string('outbound_proxy', 255)->nullable();
            $table->string('realm', 100)->nullable(); // SIP realm for auth
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->json('options')->nullable(); // Additional SIP options
            $table->timestamps();

            $table->index('user_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sip_accounts');
    }
};
