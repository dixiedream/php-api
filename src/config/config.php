<?php

/**
 * DB setup
 */
use Propel\Runtime\Propel;

$conf = [
    'classname' => 'Propel\\Runtime\\Connection\\ConnectionWrapper',
    'dsn' => 'mysql:host=database;dbname=' . getenv('DB_DATABASE'),
    'user' => getenv('DB_USER'),
    'password' => getenv('DB_PASSWORD'),
    'settings' => [
        'charset' => 'utf8mb4',
        'queries' => [
            'utf8' => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, COLLATION_CONNECTION = utf8mb4_unicode_ci, COLLATION_DATABASE = utf8mb4_unicode_ci, COLLATION_SERVER = utf8mb4_unicode_ci',
        ],
    ],
    'model_paths' => [
        0 => 'src',
        1 => 'vendor',
    ],
];

$serviceContainer = Propel::getServiceContainer();
$serviceContainer->checkVersion('2.0.0-dev');
$serviceContainer->setAdapterClass(getenv('DB_DATABASE'), 'mysql');
$manager = new \Propel\Runtime\Connection\ConnectionManagerSingle();
$manager->setConfiguration($conf);
$manager->setName(getenv('DB_DATABASE'));
$serviceContainer->setConnectionManager(getenv('DB_DATABASE'), $manager);
$serviceContainer->setDefaultDatasource(getenv('DB_DATABASE'));
