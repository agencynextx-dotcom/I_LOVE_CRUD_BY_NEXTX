<?php

declare(strict_types=1);

require_once __DIR__ . '/../Includes/functions.php';

session_unset();
session_destroy();

redirect('login.php');
