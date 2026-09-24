<?php

declare(strict_types=1);

namespace Shabstagram\Support;

/**
 * Validation du numéro IDE / UID d'une entreprise suisse, format
 * "CHE-XXX.XXX.XXX" (celui attendu par l'API des feuilles officielles).
 */
final class Uid
{
    private const PATTERN = '/^CHE-\d{3}\.\d{3}\.\d{3}$/';

    public static function isValid(string $uid): bool
    {
        return (bool) preg_match(self::PATTERN, trim($uid));
    }

    /**
     * Reformate une saisie tolérante (espaces, minuscules, tirets/points
     * manquants) vers le format canonique attendu, ou retourne null si la
     * saisie ne contient pas 9 chiffres exploitables.
     */
    public static function normalize(string $input): ?string
    {
        $digits = preg_replace('/\D/', '', $input);
        if (!is_string($digits) || strlen($digits) !== 9) {
            return null;
        }

        $formatted = sprintf(
            'CHE-%s.%s.%s',
            substr($digits, 0, 3),
            substr($digits, 3, 3),
            substr($digits, 6, 3)
        );

        return self::isValid($formatted) ? $formatted : null;
    }
}
