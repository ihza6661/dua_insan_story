<?php

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/storage/{path}', function (string $path) {
    $disk = Storage::disk('public');

    if (!$disk->exists($path)) {
        abort(404);
    }

    $contents = $disk->get($path);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_buffer($finfo, $contents) : null;
    if ($finfo) {
        finfo_close($finfo);
    }

    return Response::make($contents, 200, [
        'Content-Type' => $mimeType ?: 'application/octet-stream',
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->where('path', '.*');
