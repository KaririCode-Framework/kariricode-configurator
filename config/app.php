<?php

return [
    'name' => 'KaririCode Application',
    'version' => '1.0.0',
    'environment' => 'develop',
    'debug' => true,
    'timezone' => 'UTC',
    'locale' => 'pt-br',
    'url' => 'https://kariricode.org',
    'key' => 'base64:your-secret-key-here',
    'cipher' => 'AES-256-CBC',
    'providers' => [
        'KaririCode\Framework\Providers\AppServiceProvider',
        'KaririCode\Framework\Providers\AuthServiceProvider',
        'KaririCode\Framework\Providers\EventServiceProvider',
        'KaririCode\Framework\Providers\RouteServiceProvider',
    ],
    'aliases' => [
        'App' => 'KaririCode\Framework\Support\Facades\App',
        'Auth' => 'KaririCode\Framework\Support\Facades\Auth',
        'DB' => 'KaririCode\Framework\Support\Facades\DB',
        'Cache' => 'KaririCode\Framework\Support\Facades\Cache',
    ],
];
