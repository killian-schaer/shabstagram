<?php

declare(strict_types=1);

namespace Shabstagram\Auth;

use RuntimeException;
use Shabstagram\Support\Config;
use TheNetworg\OAuth2\Client\Provider\Azure;

/**
 * Connexion des utilisateurs via leur compte professionnel (flux délégué,
 * "authorization code"). Ne sert qu'à identifier l'utilisateur (openid,
 * profile, email, User.Read) — l'envoi de courriels et la recherche
 * d'annuaire utilisent un jeton applicatif séparé, voir Graph\GraphClient.
 */
final class EntraAuth
{
    private Azure $provider;

    public function __construct()
    {
        $this->provider = new Azure([
            'clientId' => (string) Config::get('entra.client_id'),
            'clientSecret' => (string) Config::get('entra.client_secret'),
            'redirectUri' => (string) Config::get('entra.redirect_uri'),
            'tenant' => (string) Config::get('entra.tenant_id'),
            'scope' => (string) Config::get('entra.login_scopes'),
        ]);
    }

    public function buildAuthorizationUrl(): string
    {
        $url = $this->provider->getAuthorizationUrl();
        $_SESSION['entra_oauth2_state'] = $this->provider->getState();

        return $url;
    }

    /**
     * @return array{oid:string,upn:string,name:string}
     */
    public function handleCallback(string $code, string $state): array
    {
        $expectedState = $_SESSION['entra_oauth2_state'] ?? null;
        unset($_SESSION['entra_oauth2_state']);

        if ($expectedState === null || !hash_equals((string) $expectedState, $state)) {
            throw new RuntimeException('État OAuth2 invalide (jeton anti-CSRF non reconnu).');
        }

        $token = $this->provider->getAccessToken('authorization_code', ['code' => $code]);
        $owner = $this->provider->getResourceOwner($token);
        $claims = $owner->toArray();

        $oid = (string) ($claims['oid'] ?? $claims['sub'] ?? '');
        $upn = (string) ($claims['upn'] ?? $claims['preferred_username'] ?? $claims['email'] ?? '');
        $name = (string) ($claims['name'] ?? $upn);

        if ($oid === '' || $upn === '') {
            throw new RuntimeException('Identité incomplète renvoyée par le fournisseur de connexion.');
        }

        return ['oid' => $oid, 'upn' => $upn, 'name' => $name];
    }
}
