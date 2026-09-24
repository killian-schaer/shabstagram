<?php

declare(strict_types=1);

namespace Shabstagram\Support;

/**
 * Message ponctuel affiché une seule fois après une action (ex: après un
 * enregistrement, une suppression), stocké en session le temps d'une
 * redirection.
 */
final class Flash
{
    public static function set(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    /**
     * @return array{type:string,message:string}|null
     */
    public static function consume(): ?array
    {
        if (!isset($_SESSION['flash'])) {
            return null;
        }

        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        return $flash;
    }
}
