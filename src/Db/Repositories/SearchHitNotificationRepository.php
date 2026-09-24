<?php

declare(strict_types=1);

namespace Shabstagram\Db\Repositories;

use PDO;

final class SearchHitNotificationRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Prépare une ligne de suivi d'envoi par destinataire pour un nouveau
     * résultat trouvé. Ignore silencieusement si elle existe déjà (relance
     * du cron le même jour).
     *
     * @param array<int,array{email:string,name:string,role:string}> $recipients
     */
    public function createPending(int $searchHitId, array $recipients): void
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO search_hit_notifications
                (search_hit_id, recipient_email, recipient_name, recipient_role)
             VALUES (:hit_id, :email, :name, :role)'
        );

        foreach ($recipients as $recipient) {
            $stmt->execute([
                'hit_id' => $searchHitId,
                'email' => $recipient['email'],
                'name' => $recipient['name'],
                'role' => $recipient['role'],
            ]);
        }
    }

    /**
     * Toutes les notifications encore à envoyer, avec le détail nécessaire
     * au rendu du courriel — regroupement par destinataire fait ensuite en
     * mémoire par Shabstagram\Notify\DigestBuilder.
     *
     * @return array[]
     */
    public function pending(): array
    {
        $stmt = $this->db->query(
            "SELECT
                shn.id AS notification_id,
                shn.recipient_email,
                shn.recipient_name,
                s.id AS search_id,
                s.label AS search_label,
                p.title_fr, p.title_de, p.title_it, p.title_en,
                p.rubric, p.publication_date, p.pdf_path,
                t.label AS tenant_label
             FROM search_hit_notifications shn
             INNER JOIN search_hits sh ON sh.id = shn.search_hit_id
             INNER JOIN searches s ON s.id = sh.search_id
             INNER JOIN publications p ON p.id = sh.publication_id
             INNER JOIN tenants t ON t.id = p.tenant_id
             WHERE shn.sent_at IS NULL
             ORDER BY shn.recipient_email, p.publication_date DESC"
        );

        return $stmt->fetchAll();
    }

    public function markSent(array $notificationIds): void
    {
        if ($notificationIds === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($notificationIds), '?'));
        $stmt = $this->db->prepare(
            "UPDATE search_hit_notifications SET sent_at = NOW(), attempted_at = NOW()
             WHERE id IN ($placeholders)"
        );
        $stmt->execute($notificationIds);
    }

    public function markFailed(array $notificationIds, string $errorMessage): void
    {
        if ($notificationIds === []) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($notificationIds), '?'));
        $stmt = $this->db->prepare(
            "UPDATE search_hit_notifications SET attempted_at = NOW(), error_message = ?
             WHERE id IN ($placeholders)"
        );
        $stmt->execute([$errorMessage, ...$notificationIds]);
    }
}
