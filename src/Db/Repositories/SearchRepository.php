<?php

declare(strict_types=1);

namespace Shabstagram\Db\Repositories;

use PDO;

final class SearchRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM searches WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @return array[]
     */
    public function listForOwner(int $ownerUserId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM searches WHERE owner_user_id = :owner ORDER BY created_at DESC'
        );
        $stmt->execute(['owner' => $ownerUserId]);

        return $stmt->fetchAll();
    }

    /**
     * Recherches actives, chacune associée à la liste de ses feuilles
     * officielles ciblées — utilisé par le cron principal.
     *
     * @return array[] chaque ligne : colonnes de "searches" + 'tenant_ids' => int[]
     */
    public function listActiveWithTenants(): array
    {
        $stmt = $this->db->query('SELECT * FROM searches WHERE active = 1 ORDER BY id');
        $searches = $stmt->fetchAll();

        if ($searches === []) {
            return [];
        }

        $tenantStmt = $this->db->prepare('SELECT tenant_id FROM search_tenants WHERE search_id = :id');

        foreach ($searches as &$search) {
            $tenantStmt->execute(['id' => $search['id']]);
            $search['tenant_ids'] = array_map('intval', array_column($tenantStmt->fetchAll(), 'tenant_id'));
        }

        return $searches;
    }

    public function create(
        int $ownerUserId,
        string $label,
        string $mode,
        ?string $keyword,
        ?string $uid,
        ?int $pairedSearchId = null
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO searches (owner_user_id, label, mode, keyword, uid, paired_search_id)
             VALUES (:owner, :label, :mode, :keyword, :uid, :paired)'
        );
        $stmt->execute([
            'owner' => $ownerUserId,
            'label' => $label,
            'mode' => $mode,
            'keyword' => $keyword,
            'uid' => $uid,
            'paired' => $pairedSearchId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, string $label, string $mode, ?string $keyword, ?string $uid): void
    {
        $stmt = $this->db->prepare(
            'UPDATE searches SET label = :label, mode = :mode, keyword = :keyword, uid = :uid WHERE id = :id'
        );
        $stmt->execute([
            'label' => $label,
            'mode' => $mode,
            'keyword' => $keyword,
            'uid' => $uid,
            'id' => $id,
        ]);
    }

    public function setPairedSearchId(int $id, ?int $pairedSearchId): void
    {
        $stmt = $this->db->prepare('UPDATE searches SET paired_search_id = :paired WHERE id = :id');
        $stmt->execute(['paired' => $pairedSearchId, 'id' => $id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $stmt = $this->db->prepare('UPDATE searches SET active = :active WHERE id = :id');
        $stmt->execute(['active' => $active ? 1 : 0, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM searches WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function attachTenants(int $searchId, array $tenantIds): void
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO search_tenants (search_id, tenant_id) VALUES (:search_id, :tenant_id)'
        );
        foreach ($tenantIds as $tenantId) {
            $stmt->execute(['search_id' => $searchId, 'tenant_id' => (int) $tenantId]);
        }
    }

    public function replaceTenants(int $searchId, array $tenantIds): void
    {
        $delete = $this->db->prepare('DELETE FROM search_tenants WHERE search_id = :id');
        $delete->execute(['id' => $searchId]);
        $this->attachTenants($searchId, $tenantIds);
    }
}
