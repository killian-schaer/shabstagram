<?php

declare(strict_types=1);

use Shabstagram\Support\Lang;

/**
 * Petits raccourcis utilisés dans les vues, pour éviter de répéter
 * Shabstagram\Support\Lang::t(...) et htmlspecialchars(...) partout.
 */
if (!function_exists('t')) {
    function t(string $key, array $vars = []): string
    {
        return Lang::t($key, $vars);
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
