<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Shabstagram\Auth\Session;
use Shabstagram\Db\Database;
use Shabstagram\Db\Repositories\SearchHitRepository;
use Shabstagram\Db\Repositories\UserRepository;

$currentUser = Session::requireAuth();
$db = Database::connection();
$hitRepository = new SearchHitRepository($db);
$userRepository = new UserRepository($db);

$lastVisitAt = null;
if ($currentUser['id'] !== null) {
    $userRow = $userRepository->findById($currentUser['id']);
    $lastVisitAt = $userRow['last_feed_visit_at'] ?? null;
}

$items = $hitRepository->listFeedFor($currentUser['id'], $currentUser['is_admin']);

if ($currentUser['id'] !== null) {
    $userRepository->touchFeedVisit($currentUser['id']);
}

$pageTitle = t('feed.title');
require __DIR__ . '/../views/partials/header.php';
?>

<h1 class="h3 mb-3"><?= e(t('feed.title')) ?></h1>

<?php if ($items === []): ?>
    <p class="text-muted"><?= e(t('feed.empty')) ?></p>
<?php else: ?>
    <div class="alert alert-warning"><?= e(t('results.legal_notice')) ?></div>

    <div class="list-group">
        <?php foreach ($items as $item): ?>
            <?php $isNew = $lastVisitAt !== null && strtotime((string) $item['matched_at']) > strtotime((string) $lastVisitAt); ?>
            <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <?php if ($isNew): ?>
                            <span class="badge bg-danger me-2"><?= e(t('feed.new_badge')) ?></span>
                        <?php endif; ?>
                        <span class="fw-bold"><?= e($item['title_fr'] ?: $item['title_de']) ?></span>
                        <div class="small text-muted">
                            <?= e(t('feed.column_search', ['label' => $item['search_label']])) ?>
                            — <?= e($item['tenant_label']) ?>
                            — rubrique <?= e($item['rubric']) ?>
                            — <?= e($item['publication_date']) ?>
                        </div>
                        <?php if (!empty($item['pdf_path'])): ?>
                            <div class="small">
                                <a href="/searches/pdf.php?hit=<?= (int) $item['hit_id'] ?>" target="_blank" rel="noopener">Ouvrir le document PDF officiel</a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <span class="text-muted small"><?= e($item['matched_at']) ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../views/partials/footer.php'; ?>
