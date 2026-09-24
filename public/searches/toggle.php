<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Shabstagram\Auth\Session;
use Shabstagram\Db\Database;
use Shabstagram\Db\Repositories\EventRepository;
use Shabstagram\Db\Repositories\SearchRepository;
use Shabstagram\Support\Flash;

$currentUser = Session::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /searches/index.php');
    exit;
}

$db = Database::connection();
$searchRepository = new SearchRepository($db);
$eventRepository = new EventRepository($db);

$searchId = (int) ($_POST['id'] ?? 0);
$search = $searchRepository->findById($searchId);

if ($search !== null && ($currentUser['id'] === null || (int) $search['owner_user_id'] === (int) $currentUser['id'])) {
    $newActive = !((int) $search['active'] === 1);
    $searchRepository->setActive($searchId, $newActive);
    $eventRepository->log($searchId, 'search_toggled', $newActive ? 'Recherche activée' : 'Recherche suspendue');
    Flash::set('success', $newActive ? t('search_actions.activated_flash') : t('search_actions.deactivated_flash'));
}

header('Location: /searches/index.php');
exit;
