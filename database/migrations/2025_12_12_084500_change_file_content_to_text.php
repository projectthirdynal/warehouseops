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
        Schema::table('uploads', function (Blueprint $table) {
            // Drop binary column and recreate as text for base64 storage
            $table->dropColumn('file_content');
        });
        
        Schema::table('uploads', function (Blueprint $table) {
             // longText for MySQL, text for Postgres (unlimited)
            $table->longText('file_content')->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropColumn('file_content');
        });
        
        Schema::table('uploads', function (Blueprint $table) {
             $table->binary('file_content')->nullable();
        });
    }
};
