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
    Log::info('Storage request', ['path' => $path, 'resolved' => $fullPath, 'exists' => $fileExists]);

    if (!$fileExists) {
        abort(404);
    }

    return Response::file($fullPath, [
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->where('path', '.*');
