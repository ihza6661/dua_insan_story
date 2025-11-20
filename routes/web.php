<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . ltrim($path, '/'));

    $fileExists = is_file($fullPath);
    $debugPayload = ['path' => $path, 'resolved' => $fullPath, 'exists' => $fileExists];
    Log::info('Storage request', $debugPayload);
    error_log('Storage request: ' . json_encode($debugPayload));

    if (!$fileExists) {
        abort(404);
    }

    return Response::file($fullPath, [
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->where('path', '.*');
