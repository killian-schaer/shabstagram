<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use GuzzleHttp\Client;
use Shabstagram\Auth\Session;
use Shabstagram\Db\Database;
use Shabstagram\Db\Repositories\EventRepository;
use Shabstagram\Db\Repositories\SearchRepository;
use Shabstagram\Db\Repositories\WatcherRepository;
use Shabstagram\Graph\DirectorySearch;
use Shabstagram\Graph\GraphClient;
use Shabstagram\Support\Flash;

$currentUser = Session::requireAuth();
$db = Database::connection();
$searchRepository = new SearchRepository($db);
$watcherRepository = new WatcherRepository($db);
$eventRepository = new EventRepository($db);

$searchId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$search = $searchRepository->findById($searchId);

if ($search === null || ($currentUser['id'] !== null && (int) $search['owner_user_id'] !== (int) $currentUser['id'])) {
    http_response_code(404);
    exit(t('dashboard.empty'));
}

// Recherche d'annuaire en arrière-plan (utilisée par le champ de recherche de personnes).
if (($_GET['ajax'] ?? '') === 'search') {
    header('Content-Type: application/json');
    $query = (string) ($_GET['q'] ?? '');
    $directorySearch = new DirectorySearch(new GraphClient(new Client()));
    echo json_encode($directorySearch->search($query));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add') {
        $entraObjectId = trim((string) ($_POST['entra_object_id'] ?? ''));
        $displayName = trim((string) ($_POST['display_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($entraObjectId !== '' && $displayName !== '' && $email !== '') {
            $watcherRepository->add($searchId, $entraObjectId, $displayName, $email);
            $eventRepository->log($searchId, 'search_updated', 'Destinataire ajouté : ' . $displayName);
            Flash::set('success', t('watchers.added_flash'));
        }
    } elseif ($action === 'remove') {
        $watcherId = (int) ($_POST['watcher_id'] ?? 0);
        $watcherRepository->remove($searchId, $watcherId);
        $eventRepository->log($searchId, 'search_updated', 'Destinataire retiré (identifiant ' . $watcherId . ')');
        Flash::set('success', t('watchers.removed_flash'));
    }

    header('Location: /searches/watchers.php?id=' . $searchId);
    exit;
}

$watchers = $watcherRepository->listForSearch($searchId);

$pageTitle = t('watchers.title', ['label' => $search['label']]);
require __DIR__ . '/../../views/partials/header.php';
?>

<h1 class="h3 mb-3"><?= e(t('watchers.title', ['label' => $search['label']])) ?></h1>
<p class="text-muted"><?= e(t('watchers.intro')) ?></p>

<ul class="list-group mb-4 col-lg-8">
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <?= e($currentUser['display_name']) ?>
        <span class="badge badge-owner text-white"><?= e(t('watchers.owner_badge')) ?></span>
    </li>
    <?php if ($watchers === []): ?>
        <li class="list-group-item text-muted"><?= e(t('watchers.empty')) ?></li>
    <?php endif; ?>
    <?php foreach ($watchers as $watcher): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= e($watcher['display_name']) ?> <span class="text-muted">(<?= e($watcher['email']) ?>)</span></span>
            <form method="post" class="d-inline">
                <input type="hidden" name="id" value="<?= (int) $searchId ?>">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="watcher_id" value="<?= (int) $watcher['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger"><?= e(t('watchers.remove_button')) ?></button>
            </form>
        </li>
    <?php endforeach; ?>
</ul>

<div class="col-lg-8">
    <label for="watcherSearchInput" class="form-label"><?= e(t('watchers.search_placeholder')) ?></label>
    <input type="text" class="form-control" id="watcherSearchInput" placeholder="<?= e(t('watchers.search_placeholder')) ?>" autocomplete="off">
    <div id="watcherSearchResults" class="list-group mt-2"></div>
</div>

<form method="post" id="watcherAddForm" class="d-none">
    <input type="hidden" name="id" value="<?= (int) $searchId ?>">
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="entra_object_id" id="watcherEntraObjectId">
    <input type="hidden" name="display_name" id="watcherDisplayName">
    <input type="hidden" name="email" id="watcherEmail">
</form>

<a href="/searches/index.php" class="btn btn-outline-secondary mt-3"><?= e(t('general.back')) ?></a>

<script src="/assets/js/watcher-search.js" data-search-id="<?= (int) $searchId ?>"></script>

<?php require __DIR__ . '/../../views/partials/footer.php'; ?>
