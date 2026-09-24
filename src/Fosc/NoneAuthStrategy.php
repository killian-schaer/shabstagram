<?php

declare(strict_types=1);

namespace Shabstagram\Fosc;

/**
 * Stratégie active aujourd'hui : l'API publique des feuilles officielles ne
 * requiert aucune authentification pour la lecture des publications.
 */
final class NoneAuthStrategy implements FoscAuthStrategy
{
    public function apply(array $requestOptions): array
    {
        return $requestOptions;
    }
}
