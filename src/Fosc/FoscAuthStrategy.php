<?php

declare(strict_types=1);

namespace Shabstagram\Fosc;

/**
 * Point d'extension pour de futures modalités d'authentification, si l'API
 * des feuilles officielles change ou devient payante. Aujourd'hui, seule
 * NoneAuthStrategy existe (l'API publique ne demande aucune authentification).
 */
interface FoscAuthStrategy
{
    /**
     * @param array<string,mixed> $requestOptions Options Guzzle (headers, query, ...)
     * @return array<string,mixed> Options Guzzle éventuellement complétées
     */
    public function apply(array $requestOptions): array;
}
