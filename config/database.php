<?php

declare(strict_types=1);

return [
    'driver' => getenv('COTIZA_DB_DRIVER') ?: 'mysql',
    'mysql' => [
        'host' => getenv('COTIZA_DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('COTIZA_DB_PORT') ?: 3306),
        'database' => getenv('COTIZA_DB_NAME') ?: 'cotiza_elev',
        'username' => getenv('COTIZA_DB_USER') ?: 'root',
        'password' => getenv('COTIZA_DB_PASS') !== false ? getenv('COTIZA_DB_PASS') : '',
        'charset' => 'utf8mb4',
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
    'sqlite' => [
        'database' => getenv('COTIZA_DB_FILE') ?: ':memory:',
        'options' => [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ],
    ],
];
