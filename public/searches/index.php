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

// Un administrateur voit les recherches de tout le monde, y compris celles
// sans propriétaire, comme si elles lui appartenaient.
if ($currentUser['is_admin']) {
    $searches = $searchRepository->listAll();
} else {
    $searches = $currentUser['id'] !== null ? $searchRepository->listForOwner($currentUser['id']) : [];
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
                <?php if ($currentUser['is_admin']): ?>
                    <th><?= e(t('dashboard.column_owner')) ?></th>
                <?php endif; ?>
                <th><?= e(t('general.actions')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($searches as $search): ?>
                <?php
                    $watcherCount = count($watcherRepository->listForSearch((int) $search['id']));
                    $recipientCount = $watcherCount + ($search['owner_user_id'] !== null ? 1 : 0);
                ?>
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
                    <td><?= (int) $recipientCount ?></td>
                    <?php if ($currentUser['is_admin']): ?>
                        <td>
                            <?php if ($search['owner_user_id'] === null): ?>
                                <span class="badge bg-warning text-dark"><?= e(t('dashboard.no_owner')) ?></span>
                            <?php else: ?>
                                <?= e($search['owner_display_name'] ?? '') ?>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
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
                        <form method="post" action="/searches/delete.php" id="delete-form-<?= (int) $search['id'] ?>" class="d-inline">
                            <input type="hidden" name="id" value="<?= (int) $search['id'] ?>">
                            <button type="button" class="btn btn-sm btn-outline-danger"
                                    data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
                                    data-form-id="delete-form-<?= (int) $search['id'] ?>"
                                    data-search-label="<?= e($search['label']) ?>">
                                <?= e(t('general.delete')) ?>
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5"><?= e(t('dashboard.confirm_delete_title')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(t('general.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="confirmDeleteText" data-template="<?= e(t('dashboard.confirm_delete', ['label' => '{label}'])) ?>"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= e(t('general.cancel')) ?></button>
                <button type="button" class="btn btn-danger" id="confirmDeleteButton"><?= e(t('general.delete')) ?></button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('confirmDeleteModal').addEventListener('show.bs.modal', function (event) {
    var formId = event.relatedTarget.getAttribute('data-form-id');
    var label = event.relatedTarget.getAttribute('data-search-label');
    var textEl = document.getElementById('confirmDeleteText');
    textEl.textContent = textEl.getAttribute('data-template').replace('{label}', label);
    document.getElementById('confirmDeleteButton').setAttribute('data-form-id', formId);
});
document.getElementById('confirmDeleteButton').addEventListener('click', function () {
    var formId = this.getAttribute('data-form-id');
    document.getElementById(formId).submit();
});
</script>

<?php require __DIR__ . '/../../views/partials/footer.php'; ?>
