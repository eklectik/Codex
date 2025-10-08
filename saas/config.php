<?php
// Global configuration for the SaaS control plane.
return [
    'app' => [
        'name' => 'PBN Control SaaS',
        'url' => 'https://saas.example.com',
        'allow_origins' => ['*'],
    ],
    'admin' => [
        'email' => 'admin@demo.com',
        'password' => 'admin123',
    ],
    'security' => [
        'jwt_secret' => 'change-me-secret-key',
    ],
    'database' => [
        'driver' => 'sqlite', // mysql or sqlite
        'sqlite' => [
            'path' => __DIR__ . '/data/saas.sqlite',
        ],
        'mysql' => [
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'saas',
            'username' => 'saas',
            'password' => 'saas',
            'charset' => 'utf8mb4',
        ],
    ],
];
