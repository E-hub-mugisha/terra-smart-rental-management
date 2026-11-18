<?php

namespace Tests;

use App\Support\FileUpload;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    /** Per-test upload folders, so tests never write into public/ or uploads/. */
    protected string $uploadsRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploadsRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'terra-test-uploads-' . uniqid();
        config([
            'uploads.public_root' => $this->uploadsRoot . DIRECTORY_SEPARATOR . 'public',
            'uploads.private_root' => $this->uploadsRoot . DIRECTORY_SEPARATOR . 'uploads',
        ]);
    }

    protected function tearDown(): void
    {
        if (isset($this->uploadsRoot) && is_dir($this->uploadsRoot)) {
            File::deleteDirectory($this->uploadsRoot);
        }

        parent::tearDown();
    }

    /** Asserts an uploaded file exists in the public or private upload root. */
    protected function assertUploadExists(?string $path, bool $private = false): void
    {
        $this->assertTrue(FileUpload::exists($path, $private), "Expected uploaded file [{$path}] to exist.");
    }

    protected function assertUploadMissing(?string $path, bool $private = false): void
    {
        $this->assertFalse(FileUpload::exists($path, $private), "Expected uploaded file [{$path}] to be deleted.");
    }
}
