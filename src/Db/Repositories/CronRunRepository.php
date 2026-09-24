<?php

declare(strict_types=1);

namespace Shabstagram\Db\Repositories;

use PDO;

final class CronRunRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function recordSuccess(
        int $searchId,
        int $tenantId,
        array $params,
        string $responseXmlPath,
        int $totalResults
    ): int {
        $stmt = $this->db->prepare(
            'INSERT INTO cron_runs (search_id, tenant_id, params_json, response_xml_path, total_results, status)
             VALUES (:search_id, :tenant_id, :params, :xml_path, :total, "success")'
        );
        $stmt->execute([
            'search_id' => $searchId,
            'tenant_id' => $tenantId,
            'params' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'xml_path' => $responseXmlPath,
            'total' => $totalResults,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function recordError(int $searchId, int $tenantId, array $params, string $errorMessage): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO cron_runs (search_id, tenant_id, params_json, response_xml_path, status, error_message)
             VALUES (:search_id, :tenant_id, :params, "", "error", :error)'
        );
        $stmt->execute([
            'search_id' => $searchId,
            'tenant_id' => $tenantId,
            'params' => json_encode($params, JSON_UNESCAPED_UNICODE),
            'error' => $errorMessage,
        ]);

        return (int) $this->db->lastInsertId();
    }
}
