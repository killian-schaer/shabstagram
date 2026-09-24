<?php

declare(strict_types=1);

namespace Shabstagram\Db\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function findByEntraObjectId(string $entraObjectId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE entra_object_id = :oid LIMIT 1');
        $stmt->execute(['oid' => $entraObjectId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * Crée l'utilisateur au premier login réel, ou met à jour son nom
     * affiché s'il a changé depuis la dernière connexion.
     */
    public function upsertFromEntra(string $entraObjectId, string $upn, string $displayName): int
    {
        $existing = $this->findByEntraObjectId($entraObjectId);

        if ($existing !== null) {
            $stmt = $this->db->prepare(
                'UPDATE users SET upn = :upn, display_name = :display_name WHERE id = :id'
            );
            $stmt->execute([
                'upn' => $upn,
                'display_name' => $displayName,
                'id' => $existing['id'],
            ]);

            return (int) $existing['id'];
        }

        $stmt = $this->db->prepare(
            'INSERT INTO users (entra_object_id, upn, display_name) VALUES (:oid, :upn, :display_name)'
        );
        $stmt->execute([
            'oid' => $entraObjectId,
            'upn' => $upn,
            'display_name' => $displayName,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
