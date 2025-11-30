<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/media/{path}', function (string $path) {
    $relativePath = ltrim($path, '/');

    if (! Storage::disk('public')->exists($relativePath)) {
        abort(404);
    }

    return Storage::disk('public')->response($relativePath);
})->where('path', '.*')->name('media.stream');
