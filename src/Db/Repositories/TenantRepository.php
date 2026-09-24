<?php

declare(strict_types=1);

namespace Shabstagram\Db\Repositories;

use PDO;

final class TenantRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * @return array[]
     */
    public function allActive(): array
    {
        $stmt = $this->db->query('SELECT * FROM tenants WHERE active = 1 ORDER BY label');

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM tenants WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Le sélecteur de feuille officielle ne doit s'afficher que s'il existe
     * un choix réel à faire.
     */
    public function hasMultipleActive(): bool
    {
        return count($this->allActive()) > 1;
    }
}
