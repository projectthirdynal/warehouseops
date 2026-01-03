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
        Schema::table('waybills', function (Blueprint $table) {
            $table->index('status');
            $table->index('signing_time');
            $table->index('upload_id');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->index('assigned_to');
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waybills', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['signing_time']);
            $table->dropIndex(['upload_id']);
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['assigned_to']);
            $table->dropIndex(['uploaded_by']);
        });
    }
};
