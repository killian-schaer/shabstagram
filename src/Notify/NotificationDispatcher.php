<?php

declare(strict_types=1);

namespace Shabstagram\Notify;

use GuzzleHttp\Client;
use PDO;
use Shabstagram\Db\Repositories\EventRepository;
use Shabstagram\Db\Repositories\SearchHitNotificationRepository;
use Shabstagram\Graph\GraphClient;
use Shabstagram\Graph\MailSender;
use Shabstagram\Support\Config;
use Shabstagram\Support\Lang;

/**
 * Envoi effectif des notifications en attente, regroupées par
 * destinataire. Volontairement séparé de Shabstagram\Fosc\SearchSyncService
 * (qui prépare les notifications) : n'appeler que celui-ci permet de
 * synchroniser sans jamais envoyer de courriel.
 */
final class NotificationDispatcher
{
    public function __construct(
        private SearchHitNotificationRepository $notificationRepository,
        private EventRepository $eventRepository,
        private MailSender $mailSender,
        private TemplateRenderer $renderer,
    ) {
    }

    public static function create(PDO $db): self
    {
        return new self(
            new SearchHitNotificationRepository($db),
            new EventRepository($db),
            new MailSender(new GraphClient(new Client())),
            new TemplateRenderer(__DIR__ . '/../../templates')
        );
    }

    /**
     * @return string[] messages d'erreur rencontrés
     */
    public function sendPending(): array
    {
        $errors = [];
        $pending = $this->notificationRepository->pending();

        if ($pending === []) {
            return $errors;
        }

        $grouped = (new DigestBuilder())->groupByRecipient($pending);

        $appName = (string) Config::get('app.name');
        $appUrl = (string) Config::get('app.url');
        $accentColor = (string) Config::get('theme.accent_color');
        $legalNotice = Lang::t('results.legal_notice');

        foreach ($grouped as $email => $recipientData) {
            $hitCount = array_sum(array_map(static fn (array $s): int => count($s['hits']), $recipientData['searches']));

            $subject = trim($this->renderer->render('mail/new_hits_digest_subject', [
                'app_name' => $appName,
                'hit_count' => $hitCount,
                'is_plural' => $hitCount > 1,
            ]));

            $html = $this->renderer->render('mail/new_hits_digest', [
                'app_name' => $appName,
                'app_url' => $appUrl,
                'accent_color' => $accentColor,
                'recipient_name' => $recipientData['name'],
                'searches' => array_values($recipientData['searches']),
                'legal_notice' => $legalNotice,
            ]);

            try {
                $this->mailSender->send($email, $recipientData['name'], $subject, $html);
                $this->notificationRepository->markSent($recipientData['notification_ids']);
                foreach (array_keys($recipientData['searches']) as $sId) {
                    $this->eventRepository->log((int) $sId, 'notification_sent', 'Notification envoyée à ' . $email);
                }
            } catch (\Throwable $e) {
                $message = "Envoi de la notification à {$email} impossible : " . $e->getMessage();
                $this->notificationRepository->markFailed($recipientData['notification_ids'], $message);
                foreach (array_keys($recipientData['searches']) as $sId) {
                    $this->eventRepository->log((int) $sId, 'notification_failed', $message);
                }
                $errors[] = $message;
            }
        }

        return $errors;
    }
}
