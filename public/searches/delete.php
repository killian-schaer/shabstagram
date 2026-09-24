<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Shabstagram\Auth\Access;
use Shabstagram\Auth\Session;
use Shabstagram\Db\Database;
use Shabstagram\Db\Repositories\SearchRepository;
use Shabstagram\Support\Flash;

$currentUser = Session::requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /searches/index.php');
    exit;
}

$db = Database::connection();
$searchRepository = new SearchRepository($db);

$searchId = (int) ($_POST['id'] ?? 0);
$search = $searchRepository->findById($searchId);

if ($search !== null && Access::canAccessSearch($currentUser, $search)) {
    // Les événements, résultats et notifications liés sont supprimés en cascade (contraintes ON DELETE CASCADE).
    $searchRepository->delete($searchId);
    Flash::set('success', t('search_actions.deleted_flash'));
}

header('Location: /searches/index.php');
exit;
