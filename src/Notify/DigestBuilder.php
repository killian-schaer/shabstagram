<?php

declare(strict_types=1);

namespace Shabstagram\Notify;

/**
 * Regroupe les notifications encore à envoyer par destinataire, pour qu'une
 * personne surveillant plusieurs recherches ne reçoive qu'un seul courriel
 * par exécution du cron, même si plusieurs recherches ont trouvé des
 * résultats le même jour.
 */
final class DigestBuilder
{
    /**
     * @param array[] $pendingRows Lignes issues de SearchHitNotificationRepository::pending()
     * @return array<string, array{name:string, notification_ids:int[], searches: array<int, array{label:string, hits: array[]}>}>
     */
    public function groupByRecipient(array $pendingRows): array
    {
        $grouped = [];

        foreach ($pendingRows as $row) {
            $email = $row['recipient_email'];

            if (!isset($grouped[$email])) {
                $grouped[$email] = [
                    'name' => $row['recipient_name'],
                    'notification_ids' => [],
                    'searches' => [],
                ];
            }

            $grouped[$email]['notification_ids'][] = (int) $row['notification_id'];

            $searchId = (int) $row['search_id'];
            if (!isset($grouped[$email]['searches'][$searchId])) {
                $grouped[$email]['searches'][$searchId] = [
                    'label' => $row['search_label'],
                    'hits' => [],
                ];
            }

            $grouped[$email]['searches'][$searchId]['hits'][] = [
                'title' => $row['title_fr'] !== '' ? $row['title_fr'] : ($row['title_de'] ?? ''),
                'rubric' => $row['rubric'],
                'publication_date' => $row['publication_date'],
                'tenant_label' => $row['tenant_label'],
            ];
        }

        return $grouped;
    }
}
