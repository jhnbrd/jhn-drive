<?php

$requiredEnvironment = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'FILESYSTEM_DISK' => 'local_drive',
    'LOCAL_STORAGE_PATH' => 'storage/framework/testing/jhn-drive',
];

foreach ($requiredEnvironment as $name => $expected) {
    $actual = getenv($name);

    if ($actual !== $expected) {
        throw new RuntimeException(
            "Unsafe test configuration: {$name} must be '{$expected}', got ".var_export($actual, true).'.'
        );
    }
}

require dirname(__DIR__).'/vendor/autoload.php';
