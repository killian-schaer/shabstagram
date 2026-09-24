<?php

declare(strict_types=1);

namespace Shabstagram\Db\Repositories;

use PDO;

final class SearchHitRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Enregistre la correspondance (recherche, publication) si elle n'existe
     * pas déjà. Retourne [id, isNew] — isNew=false si le cron avait déjà vu
     * cette correspondance (idempotence des relances du même jour).
     *
     * @return array{0:int,1:bool}
     */
    public function recordMatch(int $searchId, int $publicationId): array
    {
        $insert = $this->db->prepare(
            'INSERT IGNORE INTO search_hits (search_id, publication_id) VALUES (:search_id, :publication_id)'
        );
        $insert->execute(['search_id' => $searchId, 'publication_id' => $publicationId]);

        if ($insert->rowCount() > 0) {
            return [(int) $this->db->lastInsertId(), true];
        }

        $select = $this->db->prepare(
            'SELECT id FROM search_hits WHERE search_id = :search_id AND publication_id = :publication_id LIMIT 1'
        );
        $select->execute(['search_id' => $searchId, 'publication_id' => $publicationId]);
        $row = $select->fetch();

        return [(int) $row['id'], false];
    }

    /**
     * Liste des correspondances d'une recherche, avec le détail de chaque
     * publication (pour l'affichage en plein texte des annonces trouvées).
     *
     * @return array[]
     */
    public function listForSearchWithPublication(int $searchId): array
    {
        $stmt = $this->db->prepare(
            'SELECT sh.id AS hit_id, sh.matched_at, p.*
             FROM search_hits sh
             INNER JOIN publications p ON p.id = sh.publication_id
             WHERE sh.search_id = :search_id
             ORDER BY p.publication_date DESC, sh.matched_at DESC'
        );
        $stmt->execute(['search_id' => $searchId]);

        return $stmt->fetchAll();
    }
}
