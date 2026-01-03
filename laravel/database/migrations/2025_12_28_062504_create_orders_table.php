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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->onDelete('cascade');
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade'); // The user who made the sale
            $table->string('product_name');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('PENDING'); // e.g., PENDING, SHIPPED, DELIVERED, RETURNED
            $table->text('address')->nullable(); // Capture address at time of sale
            $table->string('landmark')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
