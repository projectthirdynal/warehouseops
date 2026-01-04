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
        Schema::table('leads', function (Blueprint $table) {
            $table->string('qc_status')->default('pending')->after('status'); // pending, passed, failed, recycled
            $table->unsignedBigInteger('qc_by')->nullable()->after('qc_status');
            $table->timestamp('qc_at')->nullable()->after('qc_by');
            $table->text('qc_notes')->nullable()->after('qc_at');

            $table->foreign('qc_by')->references('id')->on('users')->nullOnDelete();
            $table->index('qc_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['qc_by']);
            $table->dropColumn(['qc_status', 'qc_by', 'qc_at', 'qc_notes']);
        });
    }
};
