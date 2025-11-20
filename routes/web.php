<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/media/{path}', function (string $path) {
    $relativePath = ltrim($path, '/');
    $fullPath = storage_path('app/public/' . $relativePath);

    $fileExists = is_file($fullPath);
    $debugPayload = ['path' => $relativePath, 'resolved' => $fullPath, 'exists' => $fileExists];
    Log::info('Media request', $debugPayload);
    error_log('Media request: ' . json_encode($debugPayload));

    if (!$fileExists) {
        abort(404);
    }

    return Response::file($fullPath, [
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->where('path', '.*')->name('media.stream');
