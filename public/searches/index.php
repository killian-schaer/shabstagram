<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Shabstagram\Auth\Session;
use Shabstagram\Db\Database;
use Shabstagram\Db\Repositories\SearchRepository;
use Shabstagram\Db\Repositories\WatcherRepository;

$currentUser = Session::requireAuth();
$db = Database::connection();
$searchRepository = new SearchRepository($db);
$watcherRepository = new WatcherRepository($db);

// La session de secours (killswitch) n'appartient à aucun utilisateur réel :
// elle voit alors l'ensemble des recherches, pour rester utilisable en développement.
if ($currentUser['id'] === null) {
    $searches = $db->query('SELECT * FROM searches ORDER BY created_at DESC')->fetchAll();
} else {
    $searches = $searchRepository->listForOwner($currentUser['id']);
}

$pageTitle = t('dashboard.title');
require __DIR__ . '/../../views/partials/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><?= e(t('dashboard.title')) ?></h1>
    <a href="/searches/create.php" class="btn btn-primary"><?= e(t('nav.new_search')) ?></a>
</div>

<?php if ($searches === []): ?>
    <p class="text-muted"><?= e(t('dashboard.empty')) ?></p>
<?php else: ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th><?= e(t('dashboard.column_label')) ?></th>
                <th><?= e(t('dashboard.column_mode')) ?></th>
                <th><?= e(t('dashboard.column_status')) ?></th>
                <th><?= e(t('dashboard.column_watchers')) ?></th>
                <th><?= e(t('general.actions')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($searches as $search): ?>
                <?php $watcherCount = count($watcherRepository->listForSearch((int) $search['id'])); ?>
                <tr>
                    <td>
                        <?= e($search['label']) ?>
                        <?php if (!empty($search['paired_search_id'])): ?>
                            <div class="small text-muted"><?= e(t('dashboard.paired_with', ['label' => $search['label']])) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $search['mode'] === 'uid'
                            ? e(t('search_form.mode_uid'))
                            : e(t('search_form.mode_plaintext')) ?>
                    </td>
                    <td>
                        <?php if ((int) $search['active'] === 1): ?>
                            <span class="badge bg-success"><?= e(t('dashboard.status_active')) ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary"><?= e(t('dashboard.status_inactive')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int) $watcherCount + 1 ?></td>
                    <td class="d-flex flex-wrap gap-2">
                        <a href="/searches/results.php?id=<?= (int) $search['id'] ?>" class="btn btn-sm btn-outline-primary"><?= e(t('dashboard.view_results')) ?></a>
                        <a href="/searches/events.php?id=<?= (int) $search['id'] ?>" class="btn btn-sm btn-outline-secondary"><?= e(t('dashboard.view_events')) ?></a>
                        <a href="/searches/watchers.php?id=<?= (int) $search['id'] ?>" class="btn btn-sm btn-outline-secondary"><?= e(t('dashboard.manage_watchers')) ?></a>
                        <a href="/searches/edit.php?id=<?= (int) $search['id'] ?>" class="btn btn-sm btn-outline-secondary"><?= e(t('general.edit')) ?></a>
                        <form method="post" action="/searches/toggle.php" class="d-inline">
                            <input type="hidden" name="id" value="<?= (int) $search['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                <?= (int) $search['active'] === 1 ? e(t('dashboard.status_inactive')) : e(t('dashboard.status_active')) ?>
                            </button>
                        </form>
                        <form method="post" action="/searches/delete.php" class="d-inline" onsubmit="return confirm('<?= e(t('dashboard.confirm_delete')) ?>');">
                            <input type="hidden" name="id" value="<?= (int) $search['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><?= e(t('general.delete')) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../../views/partials/footer.php'; ?>
