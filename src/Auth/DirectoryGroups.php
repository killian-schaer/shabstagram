<?php

declare(strict_types=1);

namespace Shabstagram\Auth;

use Shabstagram\Graph\GraphClient;

/**
 * Vérification de l'appartenance à un groupe de l'annuaire professionnel,
 * désigné par son nom (voir ENTRA_ADMIN_GROUP_NAME / ENTRA_USER_GROUP_NAME).
 * Non granulaire : seule l'appartenance elle-même compte, pas de rôles fins.
 */
final class DirectoryGroups
{
    public function __construct(private GraphClient $graph)
    {
    }

    public function resolveGroupIdByName(string $displayName): ?string
    {
        $displayName = trim($displayName);
        if ($displayName === '') {
            return null;
        }

        $escaped = str_replace("'", "''", $displayName);
        $result = $this->graph->get('/groups', [
            '$filter' => "displayName eq '{$escaped}'",
            '$select' => 'id',
        ], ['ConsistencyLevel' => 'eventual']);

        $groups = $result['value'] ?? [];

        return $groups === [] ? null : (string) $groups[0]['id'];
    }

    /**
     * @param string[] $candidateGroupIds
     * @return string[] les identifiants, parmi ceux fournis, dont l'utilisateur est membre
     */
    public function memberGroupIds(string $userObjectId, array $candidateGroupIds): array
    {
        if ($candidateGroupIds === []) {
            return [];
        }

        $result = $this->graph->post("/users/{$userObjectId}/checkMemberGroups", [
            'groupIds' => $candidateGroupIds,
        ]);

        return $result['value'] ?? [];
    }
}
