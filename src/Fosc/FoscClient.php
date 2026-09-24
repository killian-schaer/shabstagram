<?php

declare(strict_types=1);

namespace Shabstagram\Fosc;

use GuzzleHttp\Client;
use RuntimeException;

/**
 * Client pour l'API publique du portail des feuilles officielles
 * (amtsblattportal.ch). Le "tenant" (feuille officielle interrogée) est
 * distingué par le domaine appelé, pas par un simple paramètre.
 */
final class FoscClient
{
    public function __construct(
        private Client $http,
        private FoscAuthStrategy $authStrategy,
        private array $allowedRefHosts,
    ) {
    }

    /**
     * Interroge l'API de recherche pour un domaine (feuille officielle)
     * donné et retourne le XML brut de la réponse.
     *
     * @param array<string,mixed> $params
     */
    public function search(string $apiBaseDomain, array $params): string
    {
        $options = $this->authStrategy->apply([
            'query' => $params,
            'timeout' => 30,
        ]);

        $response = $this->http->get("https://{$apiBaseDomain}/api/v1/publications/xml", $options);

        return (string) $response->getBody();
    }

    /**
     * Suit le lien "ref" (URL absolue) fourni par la recherche pour
     * récupérer le XML détaillé d'une publication. Le domaine cible doit
     * figurer dans la liste de confiance (FOSC_ALLOWED_REF_HOSTS).
     */
    public function fetchPublicationXml(string $refUrl): string
    {
        $this->assertAllowedHost($refUrl);

        $options = $this->authStrategy->apply(['timeout' => 30]);
        $response = $this->http->get($refUrl, $options);

        return (string) $response->getBody();
    }

    /**
     * Récupère le PDF d'une publication, à partir de son lien "ref" XML
     * (dont le suffixe /xml est remplacé par /pdf).
     */
    public function fetchPublicationPdf(string $refUrl): string
    {
        $pdfUrl = preg_replace('#/xml$#', '/pdf', $refUrl);
        if (!is_string($pdfUrl)) {
            throw new RuntimeException("Impossible de déduire l'URL du PDF depuis : {$refUrl}");
        }

        $this->assertAllowedHost($pdfUrl);

        $options = $this->authStrategy->apply(['timeout' => 30]);
        $response = $this->http->get($pdfUrl, $options);

        return (string) $response->getBody();
    }

    private function assertAllowedHost(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host === null || $host === false) {
            throw new RuntimeException("URL invalide : {$url}");
        }

        if ($this->allowedRefHosts !== [] && !in_array($host, $this->allowedRefHosts, true)) {
            throw new RuntimeException("Hôte non autorisé pour un lien de référence : {$host}");
        }
    }
}
