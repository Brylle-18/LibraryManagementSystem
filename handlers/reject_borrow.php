<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_role(['admin', 'librarian']);

$borrowId = (int) ($_POST['borrow_id'] ?? 0);

try {
    db()->prepare(
        "UPDATE borrow_records SET status = 'rejected'
         WHERE borrow_id = :id AND status = 'pending'"
    )->execute([':id' => $borrowId]);

    set_flash('flash_success', 'Request rejected.');
} catch (PDOException $e) {
    set_flash('flash_error', 'Could not reject request.');
}

redirect('../pages/dashboard.php?page=borrow_requests');
