<?php

declare(strict_types=1);

/** @var array $formValues */
/** @var array $errors */
/** @var bool $showTenantPicker */
/** @var array $tenants */
/** @var bool $isEdit */
/** @var int|null $searchId */

$isEdit ??= false;
$searchId ??= null;
?>

<h1 class="h3 mb-4"><?= e($isEdit ? t('search_form.edit_title') : t('search_form.create_title')) ?></h1>

<?php if ($errors !== []): ?>
    <div class="alert alert-danger">
        <?= e(t('search_form.validation_error')) ?>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="col-lg-8">
    <div class="mb-3">
        <label for="label" class="form-label"><?= e(t('search_form.label')) ?></label>
        <input type="text" class="form-control" id="label" name="label" value="<?= e($formValues['label']) ?>" required>
        <div class="form-text"><?= e(t('search_form.label_help')) ?></div>
    </div>

    <div class="mb-3">
        <label class="form-label"><?= e(t('search_form.mode')) ?></label>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="mode" id="mode_plaintext" value="plaintext"
                   <?= $formValues['mode'] === 'plaintext' ? 'checked' : '' ?> onchange="shabstagramToggleMode()">
            <label class="form-check-label" for="mode_plaintext"><?= e(t('search_form.mode_plaintext')) ?></label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="mode" id="mode_uid" value="uid"
                   <?= $formValues['mode'] === 'uid' ? 'checked' : '' ?> onchange="shabstagramToggleMode()">
            <label class="form-check-label" for="mode_uid"><?= e(t('search_form.mode_uid')) ?></label>
        </div>
    </div>

    <div class="mb-3" id="block_plaintext">
        <label for="keyword" class="form-label"><?= e(t('search_form.keyword')) ?></label>
        <div class="input-group">
            <input type="text" class="form-control" id="keyword" name="keyword" value="<?= e($formValues['keyword']) ?>">
            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#keywordHelp">
                <?= e(t('search_form.keyword_help_button')) ?>
            </button>
        </div>
        <div class="collapse mt-2" id="keywordHelp">
            <div class="card card-body small"><?= t('search_form.keyword_help_content') ?></div>
        </div>
    </div>

    <div class="mb-3" id="block_uid">
        <label for="uid" class="form-label"><?= e(t('search_form.uid')) ?></label>
        <input type="text" class="form-control" id="uid" name="uid" placeholder="<?= e(t('search_form.uid_placeholder')) ?>" value="<?= e($formValues['uid']) ?>">

        <div class="alert alert-warning mt-2"><?= e(t('search_form.uid_warning')) ?></div>

        <?php if (!$isEdit): ?>
        <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="add_companion_search" id="add_companion_search"
                   value="1" <?= $formValues['add_companion_search'] ? 'checked' : '' ?> onchange="shabstagramToggleCompanion()">
            <label class="form-check-label" for="add_companion_search"><?= e(t('search_form.add_companion_search')) ?></label>
        </div>

        <div class="mt-2" id="block_companion">
            <label for="companion_keyword" class="form-label"><?= e(t('search_form.companion_keyword')) ?></label>
            <input type="text" class="form-control" id="companion_keyword" name="companion_keyword" value="<?= e($formValues['companion_keyword']) ?>">
        </div>
        <?php endif; ?>
    </div>

    <?php if ($showTenantPicker): ?>
        <div class="mb-3">
            <label class="form-label"><?= e(t('search_form.gazette_picker_label')) ?></label>
            <?php foreach ($tenants as $tenant): ?>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="tenant_ids[]" value="<?= (int) $tenant['id'] ?>"
                           id="tenant_<?= (int) $tenant['id'] ?>"
                           <?= in_array((int) $tenant['id'], $formValues['tenant_ids'], true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="tenant_<?= (int) $tenant['id'] ?>"><?= e($tenant['label']) ?></label>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary"><?= e(t('general.save')) ?></button>
    <a href="/searches/index.php" class="btn btn-outline-secondary"><?= e(t('general.cancel')) ?></a>
</form>

<script>
function shabstagramToggleMode() {
    var isUid = document.getElementById('mode_uid').checked;
    document.getElementById('block_plaintext').style.display = isUid ? 'none' : '';
    document.getElementById('block_uid').style.display = isUid ? '' : 'none';
}
function shabstagramToggleCompanion() {
    var box = document.getElementById('add_companion_search');
    var block = document.getElementById('block_companion');
    if (!box || !block) { return; }
    block.style.display = box.checked ? '' : 'none';
}
document.addEventListener('DOMContentLoaded', function () {
    shabstagramToggleMode();
    shabstagramToggleCompanion();
});
</script>
