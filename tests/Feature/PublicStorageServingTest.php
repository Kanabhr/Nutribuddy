<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicStorageServingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_uploads_are_served_through_the_configured_filesystem(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/test-image.txt', 'uploaded file content');

        $this->get(route('storage.public', ['path' => 'products/test-image.txt']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'max-age=31536000, public');
    }

    public function test_missing_uploads_return_not_found(): void
    {
        Storage::fake('public');

        $this->get(route('storage.public', ['path' => 'products/missing.jpg']))
            ->assertNotFound();
    }
}
