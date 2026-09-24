<?php

declare(strict_types=1);

namespace Shabstagram\Support;

/**
 * Petit fichier JSON sur disque, écrit par le cron principal, qui permet au
 * cron de supervision de constater que la recherche quotidienne s'est bien
 * exécutée — même si la base de données est indisponible (ce fichier est la
 * seule source de vérité indépendante de la base).
 */
final class Heartbeat
{
    public static function write(string $name, array $data): void
    {
        $dir = (string) Config::get('heartbeat.path');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = rtrim($dir, '/') . '/' . $name . '.json';
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function read(string $name): ?array
    {
        $dir = (string) Config::get('heartbeat.path');
        $path = rtrim($dir, '/') . '/' . $name . '.json';

        if (!is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);

        return is_array($data) ? $data : null;
    }
}
