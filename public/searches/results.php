<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Shabstagram\Auth\Session;
use Shabstagram\Db\Database;
use Shabstagram\Db\Repositories\SearchHitRepository;
use Shabstagram\Db\Repositories\SearchRepository;

$currentUser = Session::requireAuth();
$db = Database::connection();
$searchRepository = new SearchRepository($db);
$hitRepository = new SearchHitRepository($db);

$searchId = (int) ($_GET['id'] ?? 0);
$search = $searchRepository->findById($searchId);

if ($search === null || ($currentUser['id'] !== null && (int) $search['owner_user_id'] !== (int) $currentUser['id'])) {
    http_response_code(404);
    exit(t('dashboard.empty'));
}

$hits = $hitRepository->listForSearchWithPublication($searchId);

$pageTitle = t('results.title', ['label' => $search['label']]);
require __DIR__ . '/../../views/partials/header.php';
?>

<h1 class="h3 mb-3"><?= e(t('results.title', ['label' => $search['label']])) ?></h1>

<?php if ($hits === []): ?>
    <p class="text-muted"><?= e(t('results.empty')) ?></p>
<?php else: ?>
    <div class="alert alert-warning"><?= e(t('results.legal_notice')) ?></div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th><?= e(t('results.column_title')) ?></th>
                <th><?= e(t('results.column_rubric')) ?></th>
                <th><?= e(t('results.column_date')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($hits as $hit): ?>
                <tr>
                    <td>
                        <div class="fw-bold"><?= e($hit['title_fr'] ?: $hit['title_de']) ?></div>
                        <?php if (!empty($hit['publication_text'])): ?>
                            <div class="small text-muted"><?= e($hit['publication_text']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($hit['pdf_path'])): ?>
                            <div class="small">
                                <a href="/searches/pdf.php?hit=<?= (int) $hit['hit_id'] ?>" target="_blank" rel="noopener">Ouvrir le document PDF officiel</a>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?= e($hit['rubric']) ?></td>
                    <td><?= e($hit['publication_date']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<a href="/searches/index.php" class="btn btn-outline-secondary"><?= e(t('general.back')) ?></a>

<?php require __DIR__ . '/../../views/partials/footer.php'; ?>
