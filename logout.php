<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

session_unset();
session_destroy();

header('Location: /Library Management System/login.php');
exit;
