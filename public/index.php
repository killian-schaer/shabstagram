<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Shabstagram\Auth\Session;

Session::requireAuth();

header('Location: /searches/index.php');
exit;
