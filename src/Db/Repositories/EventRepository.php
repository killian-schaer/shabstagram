<?php

declare(strict_types=1);

namespace Shabstagram\Db\Repositories;

use PDO;

final class EventRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function log(int $searchId, string $eventType, string $description, ?array $payload = null): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO events (search_id, event_type, description, payload_json)
             VALUES (:search_id, :type, :description, :payload)'
        );
        $stmt->execute([
            'search_id' => $searchId,
            'type' => $eventType,
            'description' => $description,
            'payload' => $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);
    }

    /**
     * @return array[]
     */
    public function listForSearch(int $searchId, int $limit = 200): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM events WHERE search_id = :search_id ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue('search_id', $searchId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
