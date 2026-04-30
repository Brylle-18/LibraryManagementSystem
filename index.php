<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

if (is_logged_in()) {
    $role = $_SESSION['user']['role'] ?? 'student';
    if ($role === 'student') {
        redirect('pages/userdashboard.php');
    } else {
        redirect('pages/dashboard.php');
    }
}

redirect('pages/landingpage.php');