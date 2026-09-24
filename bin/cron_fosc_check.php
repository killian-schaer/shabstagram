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
 *
 * La recherche (Shabstagram\Fosc\SearchSyncService) et l'envoi des
 * notifications (Shabstagram\Notify\NotificationDispatcher) sont
 * volontairement deux étapes séparées : le bouton de synchronisation
 * manuelle réservé aux administrateurs (public/admin/sync.php) n'exécute
 * que la première, pour ne jamais envoyer de courriel.
 */

require __DIR__ . '/../bootstrap.php';

use Shabstagram\Db\Database;
use Shabstagram\Fosc\SearchSyncService;
use Shabstagram\Notify\NotificationDispatcher;
use Shabstagram\Support\Heartbeat;

$startedAt = date('c');
Heartbeat::write('fosc_search', ['status' => 'running', 'started_at' => $startedAt]);

$errors = [];

try {
    $db = Database::connection();
    $errors = array_merge($errors, SearchSyncService::create($db)->run());
    $errors = array_merge($errors, NotificationDispatcher::create($db)->sendPending());
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
