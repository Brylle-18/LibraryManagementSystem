<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_role(['admin', 'librarian', 'student']);

if (!csrf_validate($_POST['csrf_token'] ?? null)) {
    set_flash('flash_error', 'Your session expired. Please try again.');
    $role = $_SESSION['user']['role'] ?? '';
    redirect($role === 'student' ? '../pages/userdashboard.php?page=my_books' : '../pages/dashboard.php?page=borrow_requests');
}

$borrowId = (int) ($_POST['borrow_id'] ?? 0);
$userRole  = $_SESSION['user']['role'] ?? '';
$userId    = (int) ($_SESSION['user']['id'] ?? 0);
$redirect  = $userRole === 'student'
    ? '../pages/userdashboard.php?page=my_books'
    : '../pages/dashboard.php?page=borrow_requests';

try {
    db_transaction(function (PDO $pdo) use ($borrowId, $userRole, $userId): void {
        if ($borrowId < 1) {
            throw new RuntimeException('Invalid borrow record.');
        }

        if ($userRole === 'student') {
            $stmt = $pdo->prepare(
                'SELECT borrow_id, book_id, status FROM borrow_records WHERE borrow_id = :id AND student_id = :uid FOR UPDATE'
            );
            $stmt->execute([':id' => $borrowId, ':uid' => $userId]);
        } else {
            $stmt = $pdo->prepare(
                'SELECT borrow_id, book_id, status FROM borrow_records WHERE borrow_id = :id FOR UPDATE'
            );
            $stmt->execute([':id' => $borrowId]);
        }

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

        audit('book.return', $borrowId, 'borrow_records', ['book_id' => (int) $record['book_id']]);
    });

    set_flash('flash_success', 'Book marked as returned.');
} catch (Throwable $e) {
    set_flash('flash_error', 'Could not process that return right now.');
}

redirect($redirect);
