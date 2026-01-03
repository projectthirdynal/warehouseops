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
            if (!Schema::hasColumn('waybills', 'item_name')) {
                $table->string('item_name')->nullable()->after('remarks');
            }
        });

        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'previous_item')) {
                $table->string('previous_item')->nullable()->after('product_brand');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('waybills', function (Blueprint $table) {
            $table->dropColumn('item_name');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('previous_item');
        });
    }
};
