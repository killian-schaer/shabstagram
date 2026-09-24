<?php

declare(strict_types=1);

/**
 * Cron principal (planifié quotidiennement) : interroge les feuilles
 * officielles pour chaque recherche active, stocke les preuves brutes, puis
 * envoie les notifications par courriel groupées par destinataire.
 *
 * Ce script écrit un battement de coeur sur disque (voir Support\Heartbeat)
 * à chaque exécution, quel que soit le résultat — c'est ce fichier que le
 * cron de supervision (bin/cron_health_check.php) surveille.
 */

require __DIR__ . '/../bootstrap.php';

use GuzzleHttp\Client;
use Shabstagram\Db\Database;
use Shabstagram\Db\Repositories\CronRunRepository;
use Shabstagram\Db\Repositories\EventRepository;
use Shabstagram\Db\Repositories\PublicationRepository;
use Shabstagram\Db\Repositories\SearchHitNotificationRepository;
use Shabstagram\Db\Repositories\SearchHitRepository;
use Shabstagram\Db\Repositories\SearchRepository;
use Shabstagram\Db\Repositories\TenantRepository;
use Shabstagram\Db\Repositories\UserRepository;
use Shabstagram\Db\Repositories\WatcherRepository;
use Shabstagram\Fosc\FoscClient;
use Shabstagram\Fosc\FoscXmlParser;
use Shabstagram\Fosc\NoneAuthStrategy;
use Shabstagram\Graph\GraphClient;
use Shabstagram\Graph\MailSender;
use Shabstagram\Notify\DigestBuilder;
use Shabstagram\Notify\TemplateRenderer;
use Shabstagram\Support\Config;
use Shabstagram\Support\Heartbeat;
use Shabstagram\Support\Lang;

$startedAt = date('c');
Heartbeat::write('fosc_search', ['status' => 'running', 'started_at' => $startedAt]);

$errors = [];

try {
    $db = Database::connection();

    $searchRepository = new SearchRepository($db);
    $tenantRepository = new TenantRepository($db);
    $publicationRepository = new PublicationRepository($db);
    $hitRepository = new SearchHitRepository($db);
    $notificationRepository = new SearchHitNotificationRepository($db);
    $cronRunRepository = new CronRunRepository($db);
    $eventRepository = new EventRepository($db);
    $watcherRepository = new WatcherRepository($db);
    $userRepository = new UserRepository($db);

    $foscClient = new FoscClient(new Client(), new NoneAuthStrategy(), Config::get('fosc.allowed_ref_hosts', []));
    $xmlParser = new FoscXmlParser();

    $storagePath = rtrim((string) Config::get('storage.path'), '/');
    $today = date('Y-m-d');
    $lookbackDays = (int) Config::get('fosc.cron_lookback_days', 7);
    $dateStart = date('Y-m-d', strtotime("-{$lookbackDays} days"));

    // --- Phase 1 : recherche, pour chaque recherche active et chaque feuille officielle liée ---
    foreach ($searchRepository->listActiveWithTenants() as $search) {
        $searchId = (int) $search['id'];

        foreach ($search['tenant_ids'] as $tenantId) {
            $tenant = $tenantRepository->findById($tenantId);
            if ($tenant === null || (int) $tenant['active'] !== 1) {
                continue;
            }

            $params = [
                'publicationStates' => 'PUBLISHED',
                'publicationDate.start' => $dateStart,
                'publicationDate.end' => $today,
                'pageRequest.size' => 500,
            ];

            if ($search['mode'] === 'plaintext') {
                $params['keyword'] = $search['keyword'];
            } else {
                $params['uids'] = $search['uid'];
            }

            try {
                $xml = $foscClient->search($tenant['api_base_domain'], $params);
            } catch (\Throwable $e) {
                $message = "Recherche impossible pour « {$search['label']} » ({$tenant['label']}) : " . $e->getMessage();
                $cronRunRepository->recordError($searchId, $tenantId, $params, $message);
                $eventRepository->log($searchId, 'cron_error', $message);
                $errors[] = $message;
                continue;
            }

            $dayDir = "{$storagePath}/{$today}/{$searchId}";
            if (!is_dir($dayDir)) {
                mkdir($dayDir, 0775, true);
            }
            $responseXmlPath = "{$dayDir}/recherche-feuille-{$tenantId}.xml";
            file_put_contents($responseXmlPath, $xml);

            $parsed = $xmlParser->parseSearchResponse($xml);
            $cronRunRepository->recordSuccess($searchId, $tenantId, $params, $responseXmlPath, $parsed['total']);

            foreach ($parsed['publications'] as $publication) {
                $existing = $publicationRepository->findByTenantAndGuid($tenantId, $publication['fosc_guid']);

                if ($existing === null) {
                    try {
                        $detailXml = $foscClient->fetchPublicationXml($publication['ref_url']);
                        $detail = $xmlParser->parseDetailXml($detailXml);
                        $pdfContent = $foscClient->fetchPublicationPdf($publication['ref_url']);
                    } catch (\Throwable $e) {
                        $message = "Téléchargement impossible pour la publication {$publication['fosc_guid']} : " . $e->getMessage();
                        $eventRepository->log($searchId, 'cron_error', $message);
                        $errors[] = $message;
                        continue;
                    }

                    $guidDir = "{$storagePath}/{$today}";
                    if (!is_dir($guidDir)) {
                        mkdir($guidDir, 0775, true);
                    }
                    $xmlPath = "{$guidDir}/{$publication['fosc_guid']}.xml";
                    $pdfPath = "{$guidDir}/{$publication['fosc_guid']}.pdf";
                    file_put_contents($xmlPath, $detailXml);
                    file_put_contents($pdfPath, $pdfContent);

                    $publicationId = $publicationRepository->create($tenantId, $publication['fosc_guid'], array_merge($publication, [
                        'publication_text' => $detail['publication_text'],
                        'xml_path' => $xmlPath,
                        'pdf_path' => $pdfPath,
                    ]));
                } else {
                    $publicationId = (int) $existing['id'];
                }

                [$hitId, $isNew] = $hitRepository->recordMatch($searchId, $publicationId);

                if ($isNew) {
                    $title = $publication['title_fr'] !== '' ? $publication['title_fr'] : $publication['title_de'];
                    $eventRepository->log($searchId, 'hit_found', 'Nouvelle publication trouvée : ' . $title);

                    $recipients = [];
                    $owner = $userRepository->findById((int) $search['owner_user_id']);
                    if ($owner !== null) {
                        $recipients[] = ['email' => $owner['upn'], 'name' => $owner['display_name'], 'role' => 'owner'];
                    }
                    foreach ($watcherRepository->listForSearch($searchId) as $watcher) {
                        $recipients[] = ['email' => $watcher['email'], 'name' => $watcher['display_name'], 'role' => 'watcher'];
                    }

                    $notificationRepository->createPending($hitId, $recipients);
                }
            }
        }
    }

    // --- Phase 2 : notification, regroupée par destinataire ---
    $pending = $notificationRepository->pending();

    if ($pending !== []) {
        $grouped = (new DigestBuilder())->groupByRecipient($pending);
        $mailSender = new MailSender(new GraphClient(new Client()));
        $renderer = new TemplateRenderer(__DIR__ . '/../templates');

        $appName = (string) Config::get('app.name');
        $appUrl = (string) Config::get('app.url');
        $accentColor = (string) Config::get('theme.accent_color');
        $legalNotice = Lang::t('results.legal_notice');

        foreach ($grouped as $email => $recipientData) {
            $hitCount = array_sum(array_map(static fn (array $s): int => count($s['hits']), $recipientData['searches']));

            $subject = trim($renderer->render('mail/new_hits_digest_subject', [
                'app_name' => $appName,
                'hit_count' => $hitCount,
                'is_plural' => $hitCount > 1,
            ]));

            $html = $renderer->render('mail/new_hits_digest', [
                'app_name' => $appName,
                'app_url' => $appUrl,
                'accent_color' => $accentColor,
                'recipient_name' => $recipientData['name'],
                'searches' => array_values($recipientData['searches']),
                'legal_notice' => $legalNotice,
            ]);

            try {
                $mailSender->send($email, $recipientData['name'], $subject, $html);
                $notificationRepository->markSent($recipientData['notification_ids']);
                foreach (array_keys($recipientData['searches']) as $sId) {
                    $eventRepository->log((int) $sId, 'notification_sent', 'Notification envoyée à ' . $email);
                }
            } catch (\Throwable $e) {
                $message = "Envoi de la notification à {$email} impossible : " . $e->getMessage();
                $notificationRepository->markFailed($recipientData['notification_ids'], $message);
                foreach (array_keys($recipientData['searches']) as $sId) {
                    $eventRepository->log((int) $sId, 'notification_failed', $message);
                }
                $errors[] = $message;
            }
        }
    }
} catch (\Throwable $e) {
    $errors[] = 'Erreur inattendue : ' . $e->getMessage();
}

Heartbeat::write('fosc_search', [
    'status' => $errors === [] ? 'success' : 'error',
    'started_at' => $startedAt,
    'finished_at' => date('c'),
    'errors' => $errors,
]);

fwrite(STDOUT, $errors === [] ? "OK\n" : "Terminé avec des erreurs :\n" . implode("\n", $errors) . "\n");
