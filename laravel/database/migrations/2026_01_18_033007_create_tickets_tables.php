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
        // 1. Ticket Categories
        if (!Schema::hasTable('ticket_categories')) {
            Schema::create('ticket_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('color')->default('gray'); // for UI badges
                $table->timestamps();
            });
        }

        // 2. Tickets
        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id();
                $table->string('ref_no')->unique(); // e.g., TIC-20240101-001
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Creator
                $table->foreignId('category_id')->constrained('ticket_categories');
                $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null'); // IT Staff
                $table->string('subject');
                $table->text('description');
                $table->enum('priority', ['low', 'normal', 'high', 'critical'])->default('normal');
                $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. Ticket Messages (Comments/Updates)
        if (!Schema::hasTable('ticket_messages')) {
            Schema::create('ticket_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Commenter
                $table->text('message');
                $table->json('attachments')->nullable(); // Arrays of file paths
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_categories');
    }
};
