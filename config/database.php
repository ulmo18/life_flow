<?php

declare(strict_types=1);

$explicitDriver = getenv('DB_CONNECTION') ?: (getenv('DB_DRIVER') ?: '');

$hasNetworkDbEnv = (getenv('DB_HOST') ?: '') !== ''
    || (getenv('DB_NAME') ?: '') !== ''
    || (getenv('DB_USER') ?: '') !== '';

$driver = $explicitDriver !== ''
    ? $explicitDriver
    : ($hasNetworkDbEnv ? 'mysql' : 'sqlite');

return [
    'driver' => $driver,
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => (int) (getenv('DB_PORT') ?: 3306),
    'database' => getenv('DB_NAME') ?: 'lifeflow',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
    'sqlite_path' => getenv('SQLITE_PATH') ?: __DIR__ . '/../storage/database.sqlite',
];
