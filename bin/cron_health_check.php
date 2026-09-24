<?php

declare(strict_types=1);

/**
 * Cron de supervision, totalement indépendant du cron principal
 * (bin/cron_fosc_check.php) : il doit pouvoir alerter un administrateur
 * même si la base de données est en panne. Planifié séparément (ex: toutes
 * les heures), il ne partage aucune dépendance critique avec le cron
 * principal au-delà du fichier de configuration.
 */

require __DIR__ . '/../bootstrap.php';

use GuzzleHttp\Client;
use Shabstagram\Db\Database;
use Shabstagram\Graph\GraphClient;
use Shabstagram\Graph\MailSender;
use Shabstagram\Notify\TemplateRenderer;
use Shabstagram\Support\Config;
use Shabstagram\Support\Heartbeat;

$reasons = [];

// 1) Le cron principal s'est-il exécuté récemment, et sans erreur ?
$heartbeat = Heartbeat::read('fosc_search');
$maxAgeHours = (int) Config::get('heartbeat.max_age_hours', 26);

if ($heartbeat === null) {
    $reasons[] = 'Aucune trace d\'exécution de la recherche quotidienne n\'a été trouvée.';
} else {
    $finishedAt = $heartbeat['finished_at'] ?? null;
    if ($finishedAt === null || (time() - strtotime((string) $finishedAt)) > $maxAgeHours * 3600) {
        $reasons[] = "La recherche quotidienne ne semble plus s'exécuter depuis plus de {$maxAgeHours} heures.";
    }
    if (($heartbeat['status'] ?? '') === 'error') {
        $details = implode(' | ', (array) ($heartbeat['errors'] ?? []));
        $reasons[] = 'La dernière exécution de la recherche quotidienne a rencontré une erreur : ' . $details;
    }
}

// 2) La base de données est-elle joignable maintenant, indépendamment du point précédent ?
try {
    Database::quickProbe();
} catch (\Throwable $e) {
    $reasons[] = 'La base de données est actuellement inaccessible : ' . $e->getMessage();
}

if ($reasons === []) {
    exit(0);
}

// 3) Anti-spam : ne pas ré-alerter pour la même panne avant le délai configuré.
$throttleHours = (int) Config::get('heartbeat.alert_throttle_hours', 12);
$reasonsHash = md5(implode('|', $reasons));
$lastAlert = Heartbeat::read('last_alert_sent');

$shouldSend = true;
if ($lastAlert !== null
    && ($lastAlert['reasons_hash'] ?? '') === $reasonsHash
    && isset($lastAlert['sent_at'])
    && (time() - strtotime((string) $lastAlert['sent_at'])) < $throttleHours * 3600
) {
    $shouldSend = false;
}

if (!$shouldSend) {
    exit(0);
}

try {
    $adminEmail = (string) Config::get('admin.alert_email');
    if ($adminEmail === '') {
        throw new \RuntimeException('ADMIN_ALERT_EMAIL n\'est pas configuré.');
    }

    $renderer = new TemplateRenderer(__DIR__ . '/../templates');
    $html = $renderer->render('mail/health_alert', [
        'app_name' => (string) Config::get('app.name'),
        'checked_at' => date('d.m.Y H:i'),
        'reasons' => $reasons,
    ]);

    $mailSender = new MailSender(new GraphClient(new Client()));
    $mailSender->send($adminEmail, 'Administrateur', (string) Config::get('app.name') . ' — alerte de supervision', $html);

    Heartbeat::write('last_alert_sent', ['reasons_hash' => $reasonsHash, 'sent_at' => date('c')]);
} catch (\Throwable $e) {
    $logPath = rtrim((string) Config::get('storage.log_path'), '/') . '/health_check_failures.log';
    @file_put_contents(
        $logPath,
        '[' . date('c') . '] ' . $e->getMessage() . "\n",
        FILE_APPEND
    );
}
