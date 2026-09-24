<?php

declare(strict_types=1);

/**
 * Bouton temporaire réservé aux administrateurs : déclenche la recherche
 * (Shabstagram\Fosc\SearchSyncService) sans jamais envoyer de courriel —
 * le dispatcher de notifications n'est volontairement pas appelé ici.
 */

require __DIR__ . '/../../bootstrap.php';

use Shabstagram\Auth\Session;
use Shabstagram\Db\Database;
use Shabstagram\Fosc\SearchSyncService;
use Shabstagram\Support\Flash;

$currentUser = Session::requireAuth();

if (!$currentUser['is_admin']) {
    http_response_code(403);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /searches/index.php');
    exit;
}

try {
    $errors = SearchSyncService::create(Database::connection())->run();
    Flash::set(
        $errors === [] ? 'success' : 'warning',
        $errors === [] ? t('admin.sync_success') : t('admin.sync_errors', ['count' => (string) count($errors)])
    );
} catch (\Throwable $e) {
    error_log('[shabstagram] Échec de la synchronisation manuelle : ' . $e->getMessage());
    Flash::set('danger', t('admin.sync_failed'));
}

header('Location: /searches/index.php');
exit;
