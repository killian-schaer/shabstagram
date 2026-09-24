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

$tenants = $tenantRepository->allActive();
$showTenantPicker = count($tenants) > 1;

$errors = [];
$formValues = [
    'label' => '',
    'mode' => 'plaintext',
    'keyword' => '',
    'uid' => '',
    'add_companion_search' => false,
    'companion_keyword' => '',
    'tenant_ids' => [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($currentUser['id'] === null) {
        $errors[] = 'Impossible de créer une recherche en mode de secours (aucun utilisateur réel associé).';
    }

    $formValues['label'] = trim((string) ($_POST['label'] ?? ''));
    $formValues['mode'] = ($_POST['mode'] ?? 'plaintext') === 'uid' ? 'uid' : 'plaintext';
    $formValues['keyword'] = trim((string) ($_POST['keyword'] ?? ''));
    $formValues['uid'] = trim((string) ($_POST['uid'] ?? ''));
    $formValues['add_companion_search'] = isset($_POST['add_companion_search']);
    $formValues['companion_keyword'] = trim((string) ($_POST['companion_keyword'] ?? ''));
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
    if ($formValues['mode'] === 'uid' && $formValues['add_companion_search'] && $formValues['companion_keyword'] === '') {
        $errors[] = t('search_form.companion_keyword');
    }
    if ($showTenantPicker && $formValues['tenant_ids'] === []) {
        $errors[] = t('search_form.gazette_picker_label');
    }

    if ($errors === []) {
        $tenantIds = $showTenantPicker ? $formValues['tenant_ids'] : array_map(static fn ($t) => (int) $t['id'], $tenants);

        $searchId = $searchRepository->create(
            (int) $currentUser['id'],
            $formValues['label'],
            $formValues['mode'],
            $formValues['mode'] === 'plaintext' ? $formValues['keyword'] : null,
            $formValues['mode'] === 'uid' ? $normalizedUid : null
        );
        $searchRepository->attachTenants($searchId, $tenantIds);
        $eventRepository->log($searchId, 'search_created', 'Recherche créée : ' . $formValues['label']);

        if ($formValues['mode'] === 'uid' && $formValues['add_companion_search']) {
            $companionId = $searchRepository->create(
                (int) $currentUser['id'],
                $formValues['label'] . ' (recherche complémentaire)',
                'plaintext',
                $formValues['companion_keyword'],
                null,
                $searchId
            );
            $searchRepository->attachTenants($companionId, $tenantIds);
            $searchRepository->setPairedSearchId($searchId, $companionId);
            $eventRepository->log($companionId, 'search_created', 'Recherche complémentaire créée pour : ' . $formValues['label']);
        }

        Flash::set('success', t('search_form.created_flash'));
        header('Location: /searches/index.php');
        exit;
    }
}

$pageTitle = t('search_form.create_title');
require __DIR__ . '/../../views/partials/header.php';
require __DIR__ . '/../../views/searches/form.php';
require __DIR__ . '/../../views/partials/footer.php';
