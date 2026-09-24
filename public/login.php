<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Shabstagram\Auth\EntraAuth;
use Shabstagram\Auth\Killswitch;
use Shabstagram\Support\Config;

if (Killswitch::isEnabled() || isset($_SESSION['user_id'])) {
    header('Location: /searches/index.php');
    exit;
}

$authorizationUrl = (new EntraAuth())->buildAuthorizationUrl();
$appName = (string) Config::get('app.name');
$accentColor = (string) Config::get('theme.accent_color');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(t('auth.login_title')) ?> — <?= e($appName) ?></title>
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/theme.css" rel="stylesheet">
    <style>:root { --bs-primary: <?= e($accentColor) ?>; }</style>
</head>
<body class="d-flex align-items-center" style="min-height:100vh;background-color:#f4f5f7;">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-sm-8 col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 text-center">
                    <img src="/assets/img/logo.svg" alt="<?= e($appName) ?>" width="64" height="64" class="mb-3">
                    <h1 class="h4 mb-3"><?= e($appName) ?></h1>
                    <p class="text-muted"><?= e(t('auth.login_intro')) ?></p>
                    <a href="<?= e($authorizationUrl) ?>" class="btn btn-primary w-100"><?= e(t('auth.login_button')) ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
