<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use Shabstagram\Auth\Session;
use Shabstagram\Db\Database;
use Shabstagram\Db\Repositories\EventRepository;
use Shabstagram\Db\Repositories\SearchRepository;
use Shabstagram\Db\Repositories\TenantRepository;
use Shabstagram\Support\Flash;
use Shabstagram\Support\Uid;

$currentUser = Session::requireAuth();
$db = Database::connection();
$searchRepository = new SearchRepository($db);
$tenantRepository = new TenantRepository($db);
$eventRepository = new EventRepository($db);

$searchId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$search = $searchRepository->findById($searchId);

if ($search === null || ($currentUser['id'] !== null && (int) $search['owner_user_id'] !== (int) $currentUser['id'])) {
    http_response_code(404);
    exit(t('dashboard.empty'));
}

$tenants = $tenantRepository->allActive();
$showTenantPicker = count($tenants) > 1;

$tenantIdsStmt = $db->prepare('SELECT tenant_id FROM search_tenants WHERE search_id = :id');
$tenantIdsStmt->execute(['id' => $searchId]);
$currentTenantIds = array_map('intval', array_column($tenantIdsStmt->fetchAll(), 'tenant_id'));

$errors = [];
$formValues = [
    'label' => $search['label'],
    'mode' => $search['mode'],
    'keyword' => (string) $search['keyword'],
    'uid' => (string) $search['uid'],
    'add_companion_search' => false,
    'companion_keyword' => '',
    'tenant_ids' => $currentTenantIds,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formValues['label'] = trim((string) ($_POST['label'] ?? ''));
    $formValues['mode'] = ($_POST['mode'] ?? 'plaintext') === 'uid' ? 'uid' : 'plaintext';
    $formValues['keyword'] = trim((string) ($_POST['keyword'] ?? ''));
    $formValues['uid'] = trim((string) ($_POST['uid'] ?? ''));
    $formValues['tenant_ids'] = array_map('intval', $_POST['tenant_ids'] ?? []);

    if ($formValues['label'] === '') {
        $errors[] = t('search_form.label');
    }

    $normalizedUid = null;
    if ($formValues['mode'] === 'plaintext' && $formValues['keyword'] === '') {
        $errors[] = t('search_form.keyword');
    }
    if ($formValues['mode'] === 'uid') {
        $normalizedUid = Uid::normalize($formValues['uid']);
        if ($normalizedUid === null) {
            $errors[] = t('search_form.uid_invalid');
        }
    }
    if ($showTenantPicker && $formValues['tenant_ids'] === []) {
        $errors[] = t('search_form.gazette_picker_label');
    }

    if ($errors === []) {
        $searchRepository->update(
            $searchId,
            $formValues['label'],
            $formValues['mode'],
            $formValues['mode'] === 'plaintext' ? $formValues['keyword'] : null,
            $formValues['mode'] === 'uid' ? $normalizedUid : null
        );

        if ($showTenantPicker) {
            $searchRepository->replaceTenants($searchId, $formValues['tenant_ids']);
        }

        $eventRepository->log($searchId, 'search_updated', 'Recherche modifiée : ' . $formValues['label']);
        Flash::set('success', t('search_form.updated_flash'));
        header('Location: /searches/index.php');
        exit;
    }
}

$isEdit = true;
$pageTitle = t('search_form.edit_title');
require __DIR__ . '/../../views/partials/header.php';
require __DIR__ . '/../../views/searches/form.php';
require __DIR__ . '/../../views/partials/footer.php';
