<?php

declare(strict_types=1);

namespace Shabstagram\Auth;

use Shabstagram\Support\Config;
use Shabstagram\Support\Lang;

/**
 * Coupe-circuit de développement : lorsqu'il est actif, la connexion
 * professionnelle réelle n'est jamais sollicitée. Une identité factice est
 * utilisée en mémoire uniquement — aucune ligne n'est jamais créée dans la
 * table "users" pour cette identité, afin de ne pas polluer les données
 * réelles. Ne doit jamais être activé en production.
 */
final class Killswitch
{
    public static function isEnabled(): bool
    {
        return (bool) Config::get('killswitch.enabled', false);
    }

    /**
     * @return array{id:null,display_name:string,upn:string,is_killswitch:bool,is_admin:bool}
     */
    public static function syntheticUser(): array
    {
        return [
            'id' => null,
            'display_name' => Lang::t('auth.killswitch_display_name'),
            'upn' => 'killswitch@local',
            'is_killswitch' => true,
            // Traitée comme administratrice : peut créer des recherches sans
            // propriétaire et voir l'ensemble des recherches existantes.
            'is_admin' => true,
        ];
    }
}
