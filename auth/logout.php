<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

if (is_logged_in()) {
    audit('user.logout', (int) $_SESSION['user']['id'], 'users');
}

// Prevent session fixation — regenerate before destroying
session_regenerate_id(true);
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'],   $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();
redirect('../pages/login.php');