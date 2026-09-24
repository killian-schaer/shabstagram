<?php

declare(strict_types=1);

namespace Shabstagram\Fosc;

use RuntimeException;
use SimpleXMLElement;

/**
 * Transforme les réponses XML de l'API des feuilles officielles en tableaux
 * PHP simples. Ne connaît rien du stockage ni de la base de données.
 */
final class FoscXmlParser
{
    /**
     * @return array{total:int, publications: array[]}
     */
    public function parseSearchResponse(string $xml): array
    {
        $root = $this->load($xml);

        $publications = [];
        foreach ($root->publication ?? [] as $publication) {
            /** @var SimpleXMLElement $publication */
            $meta = $publication->meta;

            $publications[] = [
                'fosc_guid' => (string) $meta->id,
                'ref_url' => (string) $publication['ref'],
                'rubric' => (string) $meta->rubric,
                'sub_rubric' => (string) $meta->subRubric,
                'publication_date' => (string) $meta->publicationDate,
                'publication_number' => (string) $meta->publicationNumber,
                'publication_state' => (string) $meta->publicationState,
                'registration_office' => (string) ($meta->registrationOffice->displayName ?? ''),
                'title_fr' => (string) ($meta->title->fr ?? ''),
                'title_de' => (string) ($meta->title->de ?? ''),
                'title_it' => (string) ($meta->title->it ?? ''),
                'title_en' => (string) ($meta->title->en ?? ''),
            ];
        }

        return [
            'total' => (int) ($root->total ?? 0),
            'publications' => $publications,
        ];
    }

    /**
     * @return array{publication_text: ?string}
     */
    public function parseDetailXml(string $xml): array
    {
        $root = $this->load($xml);
        $text = (string) ($root->content->publicationText ?? '');

        return [
            'publication_text' => $text === '' ? null : $text,
        ];
    }

    private function load(string $xml): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        $element = simplexml_load_string($xml);
        libxml_use_internal_errors($previous);

        if ($element === false) {
            throw new RuntimeException('Réponse XML invalide reçue de l\'API des feuilles officielles.');
        }

        return $element;
    }
}
