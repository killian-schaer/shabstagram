<?php

declare(strict_types=1);

namespace Shabstagram\Auth;

/**
 * Règle d'autorisation unique pour savoir qui peut voir/gérer une
 * recherche : son propriétaire, ou n'importe quel administrateur (qui voit
 * les recherches de tout le monde comme si elles lui appartenaient). Une
 * recherche sans propriétaire n'est visible que des administrateurs.
 */
final class Access
{
    /**
     * @param array{id:?int,is_admin:bool} $currentUser
     * @param array{owner_user_id:?int} $search
     */
    public static function canAccessSearch(array $currentUser, array $search): bool
    {
        if ($currentUser['is_admin']) {
            return true;
        }

        if ($currentUser['id'] === null || $search['owner_user_id'] === null) {
            return false;
        }

        return (int) $search['owner_user_id'] === (int) $currentUser['id'];
    }
}
