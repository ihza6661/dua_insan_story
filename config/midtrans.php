<?php

$config = [
    'server_key' => env('MIDTRANS_SERVER_KEY'),
    'client_key' => env('MIDTRANS_CLIENT_KEY'),
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'is_sanitized' => env('MIDTRANS_IS_SANITIZED', true),
    'is_3ds' => env('MIDTRANS_IS_3DS', true),
    'notification_url' => env('NGROK_HTTP_8000') ? env('NGROK_HTTP_8000').'/api/v1/webhook/midtrans' : env('MIDTRANS_NOTIFICATION_URL'),
];

try {
    if (app()->bound('db')) {
        $config['server_key'] = \App\Models\Setting::where('key', 'midtrans_server_key')->value('value') ?? env('MIDTRANS_SERVER_KEY');
        $config['client_key'] = \App\Models\Setting::where('key', 'midtrans_client_key')->value('value') ?? env('MIDTRANS_CLIENT_KEY');
        $config['is_production'] = (\App\Models\Setting::where('key', 'payment_gateway_mode')->value('value') === 'production') ?? env('MIDTRANS_IS_PRODUCTION', false);
    }
} catch (\Exception $e) {
    // Fallback to env variables if DB fails
}

return $config;
