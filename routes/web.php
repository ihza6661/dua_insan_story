<?php

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . ltrim($path, '/'));

    if (!is_file($fullPath)) {
        abort(404);
    }

    return Response::file($fullPath, [
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->where('path', '.*');
