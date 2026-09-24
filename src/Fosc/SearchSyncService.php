<?php

declare(strict_types=1);

namespace Shabstagram\Fosc;

use GuzzleHttp\Client;
use PDO;
use Shabstagram\Db\Repositories\CronRunRepository;
use Shabstagram\Db\Repositories\EventRepository;
use Shabstagram\Db\Repositories\PublicationRepository;
use Shabstagram\Db\Repositories\SearchHitNotificationRepository;
use Shabstagram\Db\Repositories\SearchHitRepository;
use Shabstagram\Db\Repositories\SearchRepository;
use Shabstagram\Db\Repositories\TenantRepository;
use Shabstagram\Db\Repositories\UserRepository;
use Shabstagram\Db\Repositories\WatcherRepository;
use Shabstagram\Support\Config;

/**
 * Recherche, pour chaque recherche active et chaque feuille officielle
 * liée : interroge l'API, stocke les preuves brutes (XML, PDF), enregistre
 * les nouvelles correspondances et prépare les notifications à envoyer.
 *
 * N'envoie jamais lui-même d'e-mail — voir Shabstagram\Notify\NotificationDispatcher
 * pour la phase d'envoi, volontairement séparée. C'est ce qui permet au
 * bouton de synchronisation manuelle réservé aux administrateurs
 * (public/admin/sync.php) d'exécuter cette phase sans jamais notifier
 * personne : il n'appelle simplement pas le dispatcher.
 */
final class SearchSyncService
{
    public function __construct(
        private PDO $db,
        private FoscClient $foscClient,
        private FoscXmlParser $xmlParser,
        private SearchRepository $searchRepository,
        private TenantRepository $tenantRepository,
        private PublicationRepository $publicationRepository,
        private SearchHitRepository $hitRepository,
        private SearchHitNotificationRepository $notificationRepository,
        private CronRunRepository $cronRunRepository,
        private EventRepository $eventRepository,
        private WatcherRepository $watcherRepository,
        private UserRepository $userRepository,
        private string $storagePath,
        private int $lookbackDays,
    ) {
    }

    public static function create(PDO $db): self
    {
        return new self(
            $db,
            new FoscClient(new Client(), new NoneAuthStrategy(), Config::get('fosc.allowed_ref_hosts', [])),
            new FoscXmlParser(),
            new SearchRepository($db),
            new TenantRepository($db),
            new PublicationRepository($db),
            new SearchHitRepository($db),
            new SearchHitNotificationRepository($db),
            new CronRunRepository($db),
            new EventRepository($db),
            new WatcherRepository($db),
            new UserRepository($db),
            rtrim((string) Config::get('storage.path'), '/'),
            (int) Config::get('fosc.cron_lookback_days', 7)
        );
    }

    /**
     * @return string[] messages d'erreur rencontrés (liste vide si tout s'est bien passé)
     */
    public function run(): array
    {
        $errors = [];

        $today = date('Y-m-d');
        $dateStart = date('Y-m-d', strtotime("-{$this->lookbackDays} days"));

        foreach ($this->searchRepository->listActiveWithTenants() as $search) {
            $searchId = (int) $search['id'];

            foreach ($search['tenant_ids'] as $tenantId) {
                $tenant = $this->tenantRepository->findById($tenantId);
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
                    $xml = $this->foscClient->search($tenant['api_base_domain'], $params);
                } catch (\Throwable $e) {
                    $message = "Recherche impossible pour « {$search['label']} » ({$tenant['label']}) : " . $e->getMessage();
                    $this->cronRunRepository->recordError($searchId, $tenantId, $params, $message);
                    $this->eventRepository->log($searchId, 'cron_error', $message);
                    $errors[] = $message;
                    continue;
                }

                $dayDir = "{$this->storagePath}/{$today}/{$searchId}";
                if (!is_dir($dayDir)) {
                    mkdir($dayDir, 0775, true);
                }
                $responseXmlPath = "{$dayDir}/recherche-feuille-{$tenantId}.xml";
                file_put_contents($responseXmlPath, $xml);

                $parsed = $this->xmlParser->parseSearchResponse($xml);
                $this->cronRunRepository->recordSuccess($searchId, $tenantId, $params, $responseXmlPath, $parsed['total']);

                foreach ($parsed['publications'] as $publication) {
                    $errorsForPublication = $this->storePublicationAndRecordHit($search, $searchId, $tenantId, $publication, $today);
                    $errors = array_merge($errors, $errorsForPublication);
                }
            }
        }

        return $errors;
    }

    /**
     * @return string[]
     */
    private function storePublicationAndRecordHit(array $search, int $searchId, int $tenantId, array $publication, string $today): array
    {
        $errors = [];

        $existing = $this->publicationRepository->findByTenantAndGuid($tenantId, $publication['fosc_guid']);

        if ($existing === null) {
            try {
                $detailXml = $this->foscClient->fetchPublicationXml($publication['ref_url']);
                $detail = $this->xmlParser->parseDetailXml($detailXml);
                $pdfContent = $this->foscClient->fetchPublicationPdf($publication['ref_url']);
            } catch (\Throwable $e) {
                $message = "Téléchargement impossible pour la publication {$publication['fosc_guid']} : " . $e->getMessage();
                $this->eventRepository->log($searchId, 'cron_error', $message);

                return [$message];
            }

            $guidDir = "{$this->storagePath}/{$today}";
            if (!is_dir($guidDir)) {
                mkdir($guidDir, 0775, true);
            }
            $xmlPath = "{$guidDir}/{$publication['fosc_guid']}.xml";
            $pdfPath = "{$guidDir}/{$publication['fosc_guid']}.pdf";
            file_put_contents($xmlPath, $detailXml);
            file_put_contents($pdfPath, $pdfContent);

            $publicationId = $this->publicationRepository->create($tenantId, $publication['fosc_guid'], array_merge($publication, [
                'publication_text' => $detail['publication_text'],
                'xml_path' => $xmlPath,
                'pdf_path' => $pdfPath,
            ]));
        } else {
            $publicationId = (int) $existing['id'];
        }

        [$hitId, $isNew] = $this->hitRepository->recordMatch($searchId, $publicationId);

        if ($isNew) {
            $title = $publication['title_fr'] !== '' ? $publication['title_fr'] : $publication['title_de'];
            $this->eventRepository->log($searchId, 'hit_found', 'Nouvelle publication trouvée : ' . $title);

            // Une recherche sans propriétaire n'a aucun destinataire : elle
            // reste visible des administrateurs, mais ne notifie jamais personne.
            if ($search['owner_user_id'] !== null) {
                $recipients = [];
                $owner = $this->userRepository->findById((int) $search['owner_user_id']);
                if ($owner !== null) {
                    $recipients[] = ['email' => $owner['upn'], 'name' => $owner['display_name'], 'role' => 'owner'];
                }
                foreach ($this->watcherRepository->listForSearch($searchId) as $watcher) {
                    $recipients[] = ['email' => $watcher['email'], 'name' => $watcher['display_name'], 'role' => 'watcher'];
                }

                $this->notificationRepository->createPending($hitId, $recipients);
            }
        }

        return $errors;
    }
}
