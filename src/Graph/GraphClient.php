<?php

declare(strict_types=1);

namespace Shabstagram\Graph;

use GuzzleHttp\Client;
use RuntimeException;
use Shabstagram\Support\Config;

/**
 * Jeton applicatif unique (flux "client credentials"), utilisé à la fois
 * pour l'envoi de courriels (Mail.Send) et la recherche d'annuaire
 * (User.Read.All) — ces deux permissions applicatives doivent être
 * consenties par un administrateur du tenant. Pas de jeton par utilisateur
 * à conserver ni à rafraîchir : bien plus simple à exploiter.
 */
final class GraphClient
{
    private ?string $cachedToken = null;
    private int $cachedTokenExpiresAt = 0;

    public function __construct(private Client $http)
    {
    }

    /**
     * @param array<string,mixed> $query
     * @param array<string,string> $extraHeaders
     * @return array<string,mixed>
     */
    public function get(string $path, array $query = [], array $extraHeaders = []): array
    {
        $response = $this->http->get("https://graph.microsoft.com/v1.0{$path}", [
            'headers' => array_merge(['Authorization' => 'Bearer ' . $this->getAppToken()], $extraHeaders),
            'query' => $query,
            'timeout' => 20,
        ]);

        return json_decode((string) $response->getBody(), true) ?? [];
    }

    /**
     * @param array<string,mixed> $body
     */
    public function post(string $path, array $body): void
    {
        $this->http->post("https://graph.microsoft.com/v1.0{$path}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getAppToken(),
                'Content-Type' => 'application/json',
            ],
            'json' => $body,
            'timeout' => 20,
        ]);
    }

    private function getAppToken(): string
    {
        if ($this->cachedToken !== null && time() < $this->cachedTokenExpiresAt) {
            return $this->cachedToken;
        }

        $tenantId = (string) Config::get('entra.tenant_id');
        $response = $this->http->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
            'form_params' => [
                'client_id' => (string) Config::get('entra.client_id'),
                'client_secret' => (string) Config::get('entra.client_secret'),
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials',
            ],
            'timeout' => 20,
        ]);

        $payload = json_decode((string) $response->getBody(), true);
        if (!is_array($payload) || !isset($payload['access_token'])) {
            throw new RuntimeException('Réponse inattendue lors de l\'obtention du jeton applicatif Microsoft Graph.');
        }

        $this->cachedToken = (string) $payload['access_token'];
        $this->cachedTokenExpiresAt = time() + (int) ($payload['expires_in'] ?? 3600) - 60;

        return $this->cachedToken;
    }
}
