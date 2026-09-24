<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Shabstagram\Support\Config;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/helpers.php';

if (is_file(__DIR__ . '/.env')) {
    Dotenv::createImmutable(__DIR__)->load();
}

Config::load(require __DIR__ . '/config/config.php');

date_default_timezone_set((string) Config::get('app.timezone', 'Europe/Zurich'));

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
