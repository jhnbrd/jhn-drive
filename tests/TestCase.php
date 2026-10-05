<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSafeTestEnvironment();

        // Every test receives a freshly emptied Laravel fake disk under
        // storage/framework/testing. Real drive and vault files are never used.
        Storage::fake('local_drive');

        // Deliberately run migrations only after the fail-closed checks above.
        // RefreshDatabase is avoided because its hooks run during parent::setUp().
        $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);

        if ($exitCode !== 0) {
            throw new RuntimeException('Unable to initialize the isolated test database.');
        }
    }

    private function assertSafeTestEnvironment(): void
    {
        if (!app()->environment('testing')) {
            throw new RuntimeException('Tests may only run with APP_ENV=testing.');
        }

        if (config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException('Tests require an in-memory SQLite database.');
        }

        $driveRoot = $this->normalizePath((string) config('filesystems.disks.local_drive.root'));
        $testingRoot = $this->normalizePath(storage_path('framework/testing'));

        if ($driveRoot !== $testingRoot && !str_starts_with($driveRoot, $testingRoot . '/')) {
            throw new RuntimeException('The test drive must stay inside storage/framework/testing.');
        }
    }

    private function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
