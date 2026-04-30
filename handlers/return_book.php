<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_role(['admin', 'librarian']);

$borrowId = (int) ($_POST['borrow_id'] ?? 0);

try {
    db_transaction(function (PDO $pdo) use ($borrowId): void {
        $stmt = $pdo->prepare(
            'SELECT borrow_id, book_id, status FROM borrow_records WHERE borrow_id = :id FOR UPDATE'
        );
        $stmt->execute([':id' => $borrowId]);
        $record = $stmt->fetch();

        if (!$record || $record['status'] !== 'approved') {
            throw new RuntimeException('Record not found or not approved.');
        }

        $pdo->prepare(
            "UPDATE borrow_records SET status = 'returned', return_date = CURDATE() WHERE borrow_id = :id AND status = 'approved'"
        )->execute([':id' => $borrowId]);

        $pdo->prepare(
            'UPDATE books SET available_copies = available_copies + 1 WHERE book_id = :bid'
        )->execute([':bid' => $record['book_id']]);
    });

    set_flash('flash_success', 'Book marked as returned.');
} catch (Throwable $e) {
    set_flash('flash_error', 'Could not process return: ' . $e->getMessage());
}

redirect('../pages/dashboard.php?page=borrow_requests');
