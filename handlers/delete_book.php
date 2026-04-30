<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_role(['admin', 'librarian']);

$bookId = (int) ($_POST['book_id'] ?? 0);

try {
    // Soft delete only — never SQL DELETE
    db()->prepare('UPDATE books SET is_active = 0 WHERE book_id = :id')
        ->execute([':id' => $bookId]);

    set_flash('flash_success', 'Book removed from catalogue.');
} catch (PDOException $e) {
    set_flash('flash_error', 'Could not remove book.');
}

redirect('../pages/dashboard.php?page=manage_books');
