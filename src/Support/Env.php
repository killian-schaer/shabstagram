<?php

declare(strict_types=1);

namespace Shabstagram\Support;

/**
 * Petits accesseurs typés au-dessus de $_ENV, pour éviter de relire et
 * reconvertir les mêmes valeurs partout dans le code.
 */
final class Env
{
    public static function string(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? null;
        if ($value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = $_ENV[$key] ?? null;
        if ($value === null || $value === '') {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = $_ENV[$key] ?? null;
        if ($value === null || $value === '') {
            return $default;
        }

        return (int) $value;
    }

    /**
     * @return string[]
     */
    public static function csv(string $key): array
    {
        $value = self::string($key, '');
        if ($value === null || $value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
