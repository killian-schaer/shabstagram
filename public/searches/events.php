<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Shabstagram\Auth\Access;
use Shabstagram\Auth\Session;
use Shabstagram\Db\Database;
use Shabstagram\Db\Repositories\EventRepository;
use Shabstagram\Db\Repositories\SearchRepository;

$currentUser = Session::requireAuth();
$db = Database::connection();
$searchRepository = new SearchRepository($db);
$eventRepository = new EventRepository($db);

$searchId = (int) ($_GET['id'] ?? 0);
$search = $searchRepository->findById($searchId);

if ($search === null || !Access::canAccessSearch($currentUser, $search)) {
    http_response_code(404);
    exit(t('dashboard.empty'));
}

$events = $eventRepository->listForSearch($searchId);

$pageTitle = t('events.title', ['label' => $search['label']]);
require __DIR__ . '/../../views/partials/header.php';
?>

<h1 class="h3 mb-3"><?= e(t('events.title', ['label' => $search['label']])) ?></h1>

<?php if ($events === []): ?>
    <p class="text-muted"><?= e(t('events.empty')) ?></p>
<?php else: ?>
    <ul class="list-group">
        <?php foreach ($events as $event): ?>
            <li class="list-group-item">
                <div class="d-flex justify-content-between">
                    <span class="fw-bold"><?= e(t('events.type.' . $event['event_type'])) ?></span>
                    <span class="text-muted small"><?= e($event['created_at']) ?></span>
                </div>
                <div class="small"><?= e($event['description']) ?></div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<a href="/searches/index.php" class="btn btn-outline-secondary mt-3"><?= e(t('general.back')) ?></a>

<?php require __DIR__ . '/../../views/partials/footer.php'; ?>
