<?php

declare(strict_types=1);

namespace Shabstagram\Db;

use PDO;
use Shabstagram\Support\Config;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            Config::get('db.host'),
            Config::get('db.port'),
            Config::get('db.database')
        );

        self::$connection = new PDO(
            $dsn,
            (string) Config::get('db.username'),
            (string) Config::get('db.password'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return self::$connection;
    }

    /**
     * Ouvre une connexion PDO indépendante, avec un délai de connexion
     * court, sans passer par le singleton partagé. Utilisé uniquement par
     * le cron de supervision, qui doit pouvoir constater une panne de base
     * sans dépendre du reste de l'application.
     */
    public static function quickProbe(int $timeoutSeconds = 3): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            Config::get('db.host'),
            Config::get('db.port'),
            Config::get('db.database')
        );

        return new PDO(
            $dsn,
            (string) Config::get('db.username'),
            (string) Config::get('db.password'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => $timeoutSeconds,
            ]
        );
    }
}
