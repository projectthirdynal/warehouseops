<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('courier_providers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // 'jnt', 'lbc', 'ninja_van'
            $table->string('name', 100);
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->string('base_url', 255)->nullable();
            $table->boolean('is_active')->default(false);
            $table->json('settings')->nullable(); // Extra config (webhook secret, etc.)
            $table->timestamps();
        });

        // Seed default couriers
        DB::table('courier_providers')->insert([
            [
                'code' => 'manual',
                'name' => 'Manual Entry',
                'base_url' => null,
                'is_active' => true,
                'settings' => json_encode(['description' => 'Manual status updates without API']),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'jnt',
                'name' => 'J&T Express',
                'base_url' => 'https://api.jtexpress.ph',
                'is_active' => false,
                'settings' => json_encode(['webhook_path' => '/api/courier/jnt/webhook']),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_providers');
    }
};
