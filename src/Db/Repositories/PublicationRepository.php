<?php

declare(strict_types=1);

namespace Shabstagram\Db\Repositories;

use PDO;

final class PublicationRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function findByTenantAndGuid(int $tenantId, string $foscGuid): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM publications WHERE tenant_id = :tenant_id AND fosc_guid = :guid LIMIT 1'
        );
        $stmt->execute(['tenant_id' => $tenantId, 'guid' => $foscGuid]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM publications WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(int $tenantId, string $foscGuid, array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO publications (
                tenant_id, fosc_guid, ref_url, rubric, sub_rubric, publication_date,
                publication_number, publication_state, registration_office,
                title_fr, title_de, title_it, title_en, publication_text, xml_path, pdf_path
            ) VALUES (
                :tenant_id, :fosc_guid, :ref_url, :rubric, :sub_rubric, :publication_date,
                :publication_number, :publication_state, :registration_office,
                :title_fr, :title_de, :title_it, :title_en, :publication_text, :xml_path, :pdf_path
            )'
        );

        $stmt->execute([
            'tenant_id' => $tenantId,
            'fosc_guid' => $foscGuid,
            'ref_url' => $data['ref_url'] ?? '',
            'rubric' => $data['rubric'] ?? null,
            'sub_rubric' => $data['sub_rubric'] ?? null,
            'publication_date' => $data['publication_date'] ?? null,
            'publication_number' => $data['publication_number'] ?? null,
            'publication_state' => $data['publication_state'] ?? 'PUBLISHED',
            'registration_office' => $data['registration_office'] ?? null,
            'title_fr' => $data['title_fr'] ?? null,
            'title_de' => $data['title_de'] ?? null,
            'title_it' => $data['title_it'] ?? null,
            'title_en' => $data['title_en'] ?? null,
            'publication_text' => $data['publication_text'] ?? null,
            'xml_path' => $data['xml_path'] ?? null,
            'pdf_path' => $data['pdf_path'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateState(int $id, string $publicationState): void
    {
        $stmt = $this->db->prepare('UPDATE publications SET publication_state = :state WHERE id = :id');
        $stmt->execute(['state' => $publicationState, 'id' => $id]);
    }
}
