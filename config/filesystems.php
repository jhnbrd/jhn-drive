<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local_drive" disk maps directly to the dedicated
    | host laptop directory specified in LOCAL_STORAGE_PATH.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local_drive'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */

    'disks' => [

        'local_drive' => [
            'driver' => 'local',
            'root' => (function () {
                $rawPath = env('LOCAL_STORAGE_PATH', storage_path('app/drive_storage'));
                // Normalize Windows or Unix absolute paths vs relative paths
                if (!preg_match('/^[a-zA-Z]:[\\\\\/]/', $rawPath) && !str_starts_with($rawPath, '/') && !str_starts_with($rawPath, '\\')) {
                    $resolvedPath = base_path($rawPath);
                } else {
                    $resolvedPath = $rawPath;
                }
                if (!is_dir($resolvedPath)) {
                    @mkdir($resolvedPath, 0755, true);
                }
                return $resolvedPath;
            })(),
            'throw' => false,
            'report' => false,
        ],

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
