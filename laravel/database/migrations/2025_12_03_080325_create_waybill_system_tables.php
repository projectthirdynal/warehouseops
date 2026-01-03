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
        if (!Schema::hasTable('uploads')) {
            Schema::create('uploads', function (Blueprint $table) {
                $table->id();
                $table->string('filename');
                $table->string('uploaded_by')->default('Admin');
                $table->integer('total_rows')->default(0);
                $table->integer('processed_rows')->default(0);
                $table->string('status')->default('processing');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('waybills')) {
            Schema::create('waybills', function (Blueprint $table) {
                $table->id();
                $table->string('waybill_number')->unique();
                $table->foreignId('upload_id')->constrained('uploads')->onDelete('cascade');
                $table->string('sender_name')->nullable();
                $table->text('sender_address')->nullable();
                $table->string('sender_phone')->nullable();
                $table->string('receiver_name')->nullable();
                $table->text('receiver_address')->nullable();
                $table->string('receiver_phone')->nullable();
                $table->string('destination')->nullable();
                $table->decimal('weight', 10, 2)->default(0);
                $table->integer('quantity')->default(1);
                $table->string('service_type')->default('Standard');
                $table->decimal('cod_amount', 10, 2)->default(0);
                $table->text('remarks')->nullable();
                $table->string('status')->default('pending');
                $table->boolean('batch_ready')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('batch_sessions')) {
            Schema::create('batch_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('scanned_by');
                $table->timestamp('start_time')->useCurrent();
                $table->timestamp('end_time')->nullable();
                $table->string('status')->default('active');
                $table->integer('total_scanned')->default(0);
                $table->integer('duplicate_count')->default(0);
                $table->integer('error_count')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('scanned_waybills')) {
            Schema::create('scanned_waybills', function (Blueprint $table) {
                $table->id();
                $table->string('waybill_number');
                $table->string('scanned_by');
                $table->timestamp('scan_date')->useCurrent();
                $table->foreignId('batch_session_id')->nullable()->constrained('batch_sessions')->onDelete('set null');
                $table->timestamps();
            });
        }
        
        if (!Schema::hasTable('batch_scan_items')) {
            Schema::create('batch_scan_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('batch_session_id')->constrained('batch_sessions')->onDelete('cascade');
                $table->string('waybill_number');
                $table->string('scan_type'); // valid, duplicate, error
                $table->timestamp('scan_time')->useCurrent();
                $table->string('error_message')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batch_scan_items');
        Schema::dropIfExists('scanned_waybills');
        Schema::dropIfExists('batch_sessions');
        Schema::dropIfExists('waybills');
        Schema::dropIfExists('uploads');
    }
};
