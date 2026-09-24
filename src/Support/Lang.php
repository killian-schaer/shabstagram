<?php

declare(strict_types=1);

namespace Shabstagram\Support;

/**
 * Tous les libellés visibles par l'utilisateur doivent provenir de ce
 * mécanisme et du fichier lang/{locale}.php correspondant — aucune chaîne
 * destinée à l'utilisateur ne doit être écrite en dur ailleurs dans le code.
 *
 * Le contenu des fichiers de langue est écrit par les développeurs (jamais
 * à partir d'une saisie utilisateur) : il est donc affiché tel quel, sans
 * échappement HTML automatique, ce qui permet d'y inclure ponctuellement des
 * balises simples (<b>, <i>) quand c'est nécessaire.
 */
final class Lang
{
    /** @var array<string,string>|null */
    private static ?array $strings = null;

    public static function t(string $key, array $vars = []): string
    {
        self::ensureLoaded();

        $value = self::$strings[$key] ?? $key;

        if ($vars === []) {
            return $value;
        }

        $replacements = [];
        foreach ($vars as $name => $val) {
            $replacements['{' . $name . '}'] = (string) $val;
        }

        return strtr($value, $replacements);
    }

    private static function ensureLoaded(): void
    {
        if (self::$strings !== null) {
            return;
        }

        $locale = Config::get('app.locale', 'fr');
        $path = __DIR__ . '/../../lang/' . $locale . '.php';

        if (!is_file($path)) {
            $path = __DIR__ . '/../../lang/fr.php';
        }

        self::$strings = require $path;
    }
}
