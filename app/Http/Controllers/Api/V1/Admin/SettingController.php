<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->groupBy('group');

        return response()->json(['data' => $settings]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|exists:settings,key',
            'settings.*.value' => 'nullable',
        ]);

        foreach ($data['settings'] as $item) {
            $setting = Setting::where('key', $item['key'])->first();
            if ($setting) {
                $setting->value = $item['value'];
                $setting->save();
            }
        }

        return response()->json(['message' => 'Settings updated successfully']);
    }

    public function publicPaymentConfig()
    {
        $clientKey = \App\Models\Setting::where('key', 'midtrans_client_key')->value('value') ?? env('MIDTRANS_CLIENT_KEY');
        $isProduction = (\App\Models\Setting::where('key', 'payment_gateway_mode')->value('value') === 'production') ?? env('MIDTRANS_IS_PRODUCTION', false);

        return response()->json([
            'client_key' => $clientKey,
            'is_production' => $isProduction,
            'snap_url' => $isProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js',
        ]);
    }
}
