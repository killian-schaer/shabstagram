<?php

declare(strict_types=1);

namespace Shabstagram\Support;

/**
 * Validation du numéro IDE / UID d'une entreprise suisse, format
 * "CHE-XXX.XXX.XXX" (celui attendu par l'API des feuilles officielles).
 *
 * Le chiffre de contrôle (9e chiffre) est vérifié selon l'algorithme
 * Modulo 11 décrit à l'annexe 3.4.2 de la norme eCH-0097 (« Norme
 * concernant les données Identification des entreprises ») : chaque chiffre
 * des 8 premiers emplacements est multiplié par un facteur propre à sa
 * position (5, 4, 3, 2, 7, 6, 5, 4), les produits sont additionnés, le reste
 * de la division par 11 est soustrait à 11 pour obtenir le chiffre de
 * contrôle. Un reste de 0 donne un chiffre de contrôle de 0 ; un résultat de
 * 10 signifie que le numéro n'a jamais pu être attribué (donc invalide).
 */
final class Uid
{
    private const PATTERN = '/^CHE-\d{3}\.\d{3}\.\d{3}$/';
    private const CHECK_DIGIT_WEIGHTS = [5, 4, 3, 2, 7, 6, 5, 4];

    public static function isValid(string $uid): bool
    {
        $uid = trim($uid);
        if (!preg_match(self::PATTERN, $uid)) {
            return false;
        }

        $digits = preg_replace('/\D/', '', $uid);

        return is_string($digits) && strlen($digits) === 9 && self::hasValidCheckDigit($digits);
    }

    /**
     * Reformate une saisie tolérante (espaces, minuscules, tirets/points
     * manquants) vers le format canonique attendu, ou retourne null si la
     * saisie ne contient pas 9 chiffres exploitables ou si le chiffre de
     * contrôle est incorrect.
     */
    public static function normalize(string $input): ?string
    {
        $digits = preg_replace('/\D/', '', $input);
        if (!is_string($digits) || strlen($digits) !== 9 || !self::hasValidCheckDigit($digits)) {
            return null;
        }

        return sprintf(
            'CHE-%s.%s.%s',
            substr($digits, 0, 3),
            substr($digits, 3, 3),
            substr($digits, 6, 3)
        );
    }

    /**
     * @param string $digits exactement 9 chiffres (8 significatifs + le chiffre de contrôle)
     */
    private static function hasValidCheckDigit(string $digits): bool
    {
        $expected = self::computeCheckDigit(substr($digits, 0, 8));

        return $expected !== null && $expected === (int) $digits[8];
    }

    /**
     * @param string $eightDigits exactement les 8 premiers chiffres (sans le chiffre de contrôle)
     * @return int|null null si le numéro ne peut structurellement pas être valide (résultat 10)
     */
    private static function computeCheckDigit(string $eightDigits): ?int
    {
        $sum = 0;
        foreach (self::CHECK_DIGIT_WEIGHTS as $position => $weight) {
            $sum += (int) $eightDigits[$position] * $weight;
        }

        $checkDigit = 11 - ($sum % 11);

        if ($checkDigit === 11) {
            return 0;
        }

        if ($checkDigit === 10) {
            return null;
        }

        return $checkDigit;
    }
}
