<?php

if (getenv('DB_CONNECTION')) {
    $dsn = getenv('DB_CONNECTION');
} else {
    $dsn = 'mysql:host=database;dbname=' . getenv('DB_DATABASE');
}

return [
    'propel' => [
        'paths' => [
            'schemaDir' => '.',
            'phpDir' => './src',
            'phpConfDir' => './src/config',
            'migrationDir' => './migrations',
            'sqlDir' => './sql'
        ],
        'database' => [
            'connections' => [
                getenv('DB_DATABASE') => [
                    'adapter' => 'mysql',
                    'classname' => 'Propel\Runtime\Connection\ConnectionWrapper',
                    'dsn' => $dsn,
                    'user' => getenv('DB_USER'),
                    'password' => getenv('DB_PASSWORD'),
                    'settings' => [
                        'charset' => 'utf8mb4',
                        'queries' => ['utf8' => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, COLLATION_CONNECTION = utf8mb4_unicode_ci, COLLATION_DATABASE = utf8mb4_unicode_ci, COLLATION_SERVER = utf8mb4_unicode_ci']
                    ],
                ],
            ]
        ],
        'runtime' => [
            'defaultConnection' => getenv('DB_DATABASE'),
            'connections' => [getenv('DB_DATABASE')]
        ],
        'generator' => [
            'defaultConnection' => getenv('DB_DATABASE'),
            'connections' => [getenv('DB_DATABASE')]
        ]
    ]
];
