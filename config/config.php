<?php

declare(strict_types=1);

use Shabstagram\Support\Env;

/**
 * Point de lecture unique de toute la configuration issue du fichier .env.
 * Aucun autre fichier ne doit lire $_ENV directement (voir CLAUDE.md).
 */
return [
    'app' => [
        'name' => Env::string('APP_NAME', 'Shabstagram'),
        'env' => Env::string('APP_ENV', 'local'),
        'url' => Env::string('APP_URL', ''),
        'locale' => Env::string('APP_LOCALE', 'fr'),
        'timezone' => Env::string('APP_TIMEZONE', 'Europe/Zurich'),
    ],

    'db' => [
        'host' => Env::string('DB_HOST', 'localhost'),
        'port' => Env::int('DB_PORT', 3306),
        'database' => Env::string('DB_DATABASE', ''),
        'username' => Env::string('DB_USERNAME', ''),
        'password' => Env::string('DB_PASSWORD', ''),
    ],

    'entra' => [
        'tenant_id' => Env::string('ENTRA_TENANT_ID', ''),
        'client_id' => Env::string('ENTRA_CLIENT_ID', ''),
        'client_secret' => Env::string('ENTRA_CLIENT_SECRET', ''),
        'redirect_uri' => Env::string('ENTRA_REDIRECT_URI', ''),
        'login_scopes' => Env::string('ENTRA_LOGIN_SCOPES', 'openid profile email User.Read'),
    ],

    'graph' => [
        'sender_upn' => Env::string('GRAPH_SENDER_UPN', ''),
        'sender_name' => Env::string('GRAPH_SENDER_NAME', 'Shabstagram'),
    ],

    'admin' => [
        'alert_email' => Env::string('ADMIN_ALERT_EMAIL', ''),
    ],

    'killswitch' => [
        'enabled' => Env::bool('AUTH_KILLSWITCH', false),
    ],

    'fosc' => [
        'default_base_domain' => Env::string('FOSC_API_BASE_DOMAIN', 'www.fosc.ch'),
        'allowed_ref_hosts' => Env::csv('FOSC_ALLOWED_REF_HOSTS'),
        'cron_lookback_days' => Env::int('CRON_LOOKBACK_DAYS', 7),
    ],

    'storage' => [
        'path' => Env::string('STORAGE_PATH', __DIR__ . '/../storage'),
        'log_path' => Env::string('LOG_PATH', __DIR__ . '/../var/logs'),
    ],

    'heartbeat' => [
        'path' => Env::string('HEARTBEAT_PATH', __DIR__ . '/../var/heartbeat'),
        'max_age_hours' => Env::int('HEARTBEAT_MAX_AGE_HOURS', 26),
        'alert_throttle_hours' => Env::int('ALERT_THROTTLE_HOURS', 12),
    ],

    'theme' => [
        'accent_color' => Env::string('THEME_ACCENT_COLOR', '#123475'),
    ],
];
