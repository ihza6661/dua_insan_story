<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $settings = [
            ['key' => 'midtrans_server_key', 'value' => '', 'type' => 'string', 'group' => 'payment', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'midtrans_client_key', 'value' => '', 'type' => 'string', 'group' => 'payment', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'midtrans_merchant_id', 'value' => '', 'type' => 'string', 'group' => 'payment', 'created_at' => now(), 'updated_at' => now()],
        ];

        foreach ($settings as $setting) {
            if (! DB::table('settings')->where('key', $setting['key'])->exists()) {
                DB::table('settings')->insert($setting);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', ['midtrans_server_key', 'midtrans_client_key', 'midtrans_merchant_id'])->delete();
    }
};
