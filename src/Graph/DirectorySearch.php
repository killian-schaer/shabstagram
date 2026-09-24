<?php

declare(strict_types=1);

namespace Shabstagram\Graph;

/**
 * Recherche de collègues dans l'annuaire professionnel, pour permettre à un
 * utilisateur d'ajouter d'autres personnes au suivi d'une recherche.
 */
final class DirectorySearch
{
    public function __construct(private GraphClient $graph)
    {
    }

    /**
     * @return array<int,array{id:string,displayName:string,mail:string}>
     */
    public function search(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $result = $this->graph->get('/users', [
            '$search' => '"displayName:' . $query . '"',
            '$select' => 'id,displayName,mail,userPrincipalName',
            '$top' => 10,
        ], ['ConsistencyLevel' => 'eventual']);

        $users = [];
        foreach ($result['value'] ?? [] as $user) {
            $email = $user['mail'] ?? $user['userPrincipalName'] ?? null;
            if ($email === null) {
                continue;
            }

            $users[] = [
                'id' => (string) $user['id'],
                'displayName' => (string) ($user['displayName'] ?? $email),
                'mail' => (string) $email,
            ];
        }

        return $users;
    }
}
