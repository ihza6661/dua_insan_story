<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, number, json
            $table->string('group')->default('general'); // general, payment, shipping
            $table->timestamps();
        });

        DB::table('settings')->insert([
            ['key' => 'site_title', 'value' => 'Dua Insan Story', 'type' => 'string', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'site_description', 'value' => 'Platform Undangan Digital Terbaik', 'type' => 'string', 'group' => 'general', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'payment_gateway_mode', 'value' => 'sandbox', 'type' => 'string', 'group' => 'payment', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'shipping_origin_city_id', 'value' => '365', 'type' => 'number', 'group' => 'shipping', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
