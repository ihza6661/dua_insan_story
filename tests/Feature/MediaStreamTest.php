<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaStreamTest extends TestCase
{
    public function test_it_streams_public_media_files(): void
    {
        $relativePath = 'test-media.txt';
        Storage::disk('public')->put($relativePath, 'hello media route');

        try {
            $response = $this->get('/media/' . $relativePath);

            $response->assertOk();
            $response->assertHeader('Cache-Control', 'max-age=604800, public');
            $this->assertSame(
                strlen('hello media route'),
                (int) $response->headers->get('Content-Length')
            );
        } finally {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
