<?php

declare(strict_types=1);

namespace Shabstagram\Db\Repositories;

use PDO;

final class WatcherRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * @return array[]
     */
    public function listForSearch(int $searchId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM search_watchers WHERE search_id = :id ORDER BY display_name'
        );
        $stmt->execute(['id' => $searchId]);

        return $stmt->fetchAll();
    }

    public function add(int $searchId, string $entraObjectId, string $displayName, string $email): void
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO search_watchers (search_id, entra_object_id, display_name, email)
             VALUES (:search_id, :oid, :display_name, :email)'
        );
        $stmt->execute([
            'search_id' => $searchId,
            'oid' => $entraObjectId,
            'display_name' => $displayName,
            'email' => $email,
        ]);
    }

    public function remove(int $searchId, int $watcherId): void
    {
        $stmt = $this->db->prepare('DELETE FROM search_watchers WHERE id = :id AND search_id = :search_id');
        $stmt->execute(['id' => $watcherId, 'search_id' => $searchId]);
    }
}
