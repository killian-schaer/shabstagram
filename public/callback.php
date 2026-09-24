<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Shabstagram\Auth\EntraAuth;
use Shabstagram\Auth\Killswitch;
use Shabstagram\Auth\Session;
use Shabstagram\Db\Database;
use Shabstagram\Db\Repositories\UserRepository;
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
    $userId = (new UserRepository(Database::connection()))->upsertFromEntra(
        $claims['oid'],
        $claims['upn'],
        $claims['name']
    );
    Session::login($userId, $claims['name'], $claims['upn']);

    header('Location: /searches/index.php');
    exit;
} catch (\Throwable $e) {
    error_log('[shabstagram] Échec de connexion : ' . $e->getMessage());
    Flash::set('danger', t('auth.login_error'));
    header('Location: /login.php');
    exit;
}
