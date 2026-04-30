<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

// Logged-in users go straight to the dashboard
if (is_logged_in()) {
    redirect('pages/dashboard.php');
}

// Everyone else sees the landing page
redirect('pages/landingpage.php');