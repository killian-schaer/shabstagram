<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use GuzzleHttp\Client;
use Shabstagram\Auth\DirectoryGroups;
use Shabstagram\Auth\EntraAuth;
use Shabstagram\Auth\Killswitch;
use Shabstagram\Auth\Session;
use Shabstagram\Db\Database;
use Shabstagram\Db\Repositories\UserRepository;
use Shabstagram\Graph\GraphClient;
use Shabstagram\Support\Config;
use Shabstagram\Support\Flash;

if (Killswitch::isEnabled()) {
    header('Location: /searches/index.php');
    exit;
}

$code = $_GET['code'] ?? null;
$state = $_GET['state'] ?? null;

if (!is_string($code) || !is_string($state)) {
    Flash::set('danger', t('auth.login_error'));
    header('Location: /login.php');
    exit;
}

try {
    $claims = (new EntraAuth())->handleCallback($code, $state);

    $adminGroupName = (string) Config::get('entra.admin_group_name', '');
    $userGroupName = (string) Config::get('entra.user_group_name', '');
    $isAdmin = false;

    if ($adminGroupName !== '' || $userGroupName !== '') {
        $directoryGroups = new DirectoryGroups(new GraphClient(new Client()));
        $adminGroupId = $adminGroupName !== '' ? $directoryGroups->resolveGroupIdByName($adminGroupName) : null;
        $userGroupId = $userGroupName !== '' ? $directoryGroups->resolveGroupIdByName($userGroupName) : null;

        $candidateIds = array_values(array_filter([$adminGroupId, $userGroupId]));
        $memberOf = $directoryGroups->memberGroupIds($claims['oid'], $candidateIds);

        $isAdmin = $adminGroupId !== null && in_array($adminGroupId, $memberOf, true);
        $isUser = $userGroupId !== null && in_array($userGroupId, $memberOf, true);

        if (!$isAdmin && !$isUser) {
            Flash::set('danger', t('auth.not_authorized'));
            header('Location: /login.php');
            exit;
        }
    }

    $userId = (new UserRepository(Database::connection()))->upsertFromEntra(
        $claims['oid'],
        $claims['upn'],
        $claims['name'],
        $isAdmin
    );
    Session::login($userId, $claims['name'], $claims['upn'], $isAdmin);

    header('Location: /searches/index.php');
    exit;
} catch (\Throwable $e) {
    error_log('[shabstagram] Échec de connexion : ' . $e->getMessage());
    Flash::set('danger', t('auth.login_error'));
    header('Location: /login.php');
    exit;
}
