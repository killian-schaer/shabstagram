<?php

declare(strict_types=1);

namespace Shabstagram\Auth;

use Shabstagram\Support\Config;

/**
 * Point d'entrée unique pour connaître l'utilisateur courant et protéger
 * une page. Toute page protégée doit appeler Session::requireAuth() avant
 * d'afficher quoi que ce soit.
 */
final class Session
{
    /**
     * @return array{id:?int,display_name:string,upn:string,is_killswitch:bool,is_admin:bool}
     */
    public static function requireAuth(): array
    {
        if (Killswitch::isEnabled()) {
            return Killswitch::syntheticUser();
        }

        if (!isset($_SESSION['user_id'])) {
            $appUrl = rtrim((string) Config::get('app.url', ''), '/');
            header('Location: ' . $appUrl . '/login.php');
            exit;
        }

        return [
            'id' => (int) $_SESSION['user_id'],
            'display_name' => (string) ($_SESSION['display_name'] ?? ''),
            'upn' => (string) ($_SESSION['upn'] ?? ''),
            'is_killswitch' => false,
            'is_admin' => (bool) ($_SESSION['is_admin'] ?? false),
        ];
    }

    public static function isKillswitch(): bool
    {
        return Killswitch::isEnabled();
    }

    public static function login(int $userId, string $displayName, string $upn, bool $isAdmin): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['display_name'] = $displayName;
        $_SESSION['upn'] = $upn;
        $_SESSION['is_admin'] = $isAdmin;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
