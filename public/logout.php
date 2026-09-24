<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use Shabstagram\Auth\Session;

Session::logout();

header('Location: /login.php');
exit;
