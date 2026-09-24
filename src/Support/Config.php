<?php

declare(strict_types=1);

namespace Shabstagram\Support;

/**
 * Accès en lecture à la configuration chargée depuis config/config.php
 * (elle-même entièrement dérivée du fichier .env).
 */
final class Config
{
    private static ?array $items = null;

    public static function load(array $items): void
    {
        self::$items = $items;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $dottedKey, $default = null)
    {
        if (self::$items === null) {
            throw new \RuntimeException('La configuration n\'a pas été chargée (Config::load manquant dans bootstrap.php).');
        }

        $segments = explode('.', $dottedKey);
        $value = self::$items;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
