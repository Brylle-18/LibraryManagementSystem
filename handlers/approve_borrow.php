<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_role(['admin', 'librarian']);

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    set_flash('flash_error', 'Your session expired. Please try again.');
    redirect('../pages/dashboard.php?page=borrow_requests');
}

$borrowId = (int) ($_POST['borrow_id'] ?? 0);

try {
    db_transaction(function (PDO $pdo) use ($borrowId): void {
        $stmt = $pdo->prepare(
            'SELECT br.borrow_id, br.book_id, br.status, b.available_copies
             FROM   borrow_records br
             JOIN   books b ON b.book_id = br.book_id
             WHERE  br.borrow_id = :id
             FOR UPDATE'
        );
        $stmt->execute([':id' => $borrowId]);
        $record = $stmt->fetch();

        if (!$record || $record['status'] !== 'pending') {
            throw new RuntimeException('Record not found or already processed.');
        }
        if ((int) $record['available_copies'] < 1) {
            throw new RuntimeException('No copies available.');
        }

        $pdo->prepare(
            "UPDATE borrow_records SET status = 'approved' WHERE borrow_id = :id"
        )->execute([':id' => $borrowId]);

        $pdo->prepare(
            'UPDATE books SET available_copies = available_copies - 1 WHERE book_id = :bid'
        )->execute([':bid' => $record['book_id']]);
    });

    set_flash('flash_success', 'Borrow request approved.');
} catch (Throwable $e) {
    set_flash('flash_error', 'Could not approve this request right now.');
}

redirect('../pages/dashboard.php?page=borrow_requests');
