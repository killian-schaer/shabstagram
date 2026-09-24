<?php

declare(strict_types=1);

use Shabstagram\Support\Config;
use Shabstagram\Support\Flash;

/** @var string $pageTitle */
/** @var array|null $currentUser */

$appName = (string) Config::get('app.name');
$accentColor = (string) Config::get('theme.accent_color');
$flash = Flash::consume();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> — <?= e($appName) ?></title>
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/assets/css/theme.css" rel="stylesheet">
    <style>:root { --bs-primary: <?= e($accentColor) ?>; --bs-primary-rgb: 18,52,117; }</style>
</head>
<body>
<?php if (Shabstagram\Auth\Session::isKillswitch()): ?>
    <div class="alert alert-danger rounded-0 mb-0 text-center" role="alert">
        <?= e(t('auth.killswitch_banner')) ?>
    </div>
<?php endif; ?>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: <?= e($accentColor) ?>;">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/">
            <img src="/assets/img/logo.svg" alt="<?= e($appName) ?>" width="28" height="28">
            <?= e($appName) ?>
        </a>
        <?php if ($currentUser !== null): ?>
        <div class="d-flex align-items-center gap-2">
            <a href="/searches/index.php" class="btn btn-outline-light btn-sm"><?= e(t('nav.dashboard')) ?></a>
            <a href="/feed.php" class="btn btn-outline-light btn-sm"><?= e(t('nav.feed')) ?></a>
            <?php if ($currentUser['is_admin']): ?>
                <form method="post" action="/admin/sync.php" class="d-inline">
                    <button type="submit" class="btn btn-outline-light btn-sm"><?= e(t('admin.sync_button')) ?></button>
                </form>
            <?php endif; ?>
            <span class="text-white-50 small ms-2"><?= e(t('auth.logged_in_as', ['name' => $currentUser['display_name']])) ?></span>
            <a href="/logout.php" class="btn btn-outline-light btn-sm"><?= e(t('nav.logout')) ?></a>
        </div>
        <?php endif; ?>
    </div>
</nav>

<main class="container py-4">
    <?php if ($flash !== null): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
    <?php endif; ?>
