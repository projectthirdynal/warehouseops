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
        Schema::create('agent_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // RECYCLE_ABUSE, SLOW_CONTACT, etc.
            $table->string('severity')->default('INFO'); // INFO, WARNING, CRITICAL
            $table->string('metric_value')->nullable();
            $table->string('team_average')->nullable();
            $table->text('details')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamps();
        });

        Schema::table('agent_profiles', function (Blueprint $table) {
            $table->integer('avg_time_to_first_call')->nullable(); // in seconds
            $table->float('recycle_abuse_rate')->default(0); // percentage
            $table->float('fresh_conversion_rate')->default(0); // percentage
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_flags');
        
        Schema::table('agent_profiles', function (Blueprint $table) {
            $table->dropColumn(['avg_time_to_first_call', 'recycle_abuse_rate', 'fresh_conversion_rate']);
        });
    }
};
