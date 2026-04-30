<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

// ============================================================
//  Dashboard — aggregate stats
// ============================================================
function fetch_dashboard_stats(PDO $pdo): array
{
    $row = $pdo->query(
        'SELECT
            (SELECT COUNT(*) FROM users  WHERE is_active = 1)                           AS users,
            (SELECT COUNT(*) FROM books)                                                 AS books,
            (SELECT COUNT(*) FROM borrow_records WHERE return_date IS NULL AND status = \'approved\') AS borrowed,
            (SELECT COUNT(*) FROM borrow_records WHERE return_date IS NOT NULL OR status = \'returned\') AS returned'
    )->fetch();

    return [
        'users'    => (int) ($row['users']    ?? 0),
        'books'    => (int) ($row['books']    ?? 0),
        'borrowed' => (int) ($row['borrowed'] ?? 0),
        'returned' => (int) ($row['returned'] ?? 0),
    ];
}

// ============================================================
//  Admins panel
// ============================================================
function fetch_admins(PDO $pdo): array
{
    return $pdo->query(
        'SELECT user_id, full_name, email, role
         FROM   users
         WHERE  role IN (\'admin\', \'librarian\')
           AND  is_active = 1
         ORDER  BY role, full_name
         LIMIT  20'
    )->fetchAll();
}

// ============================================================
//  Overdue borrowers — uses the v_overdue_borrows view
// ============================================================
function fetch_overdue_borrowers(PDO $pdo): array
{
    return $pdo->query(
        'SELECT u.user_id, u.full_name, u.email,
                b.title,
                br.borrow_date,
                DATEDIFF(CURDATE(), DATE_ADD(br.borrow_date, INTERVAL 14 DAY)) AS overdue_days
         FROM   borrow_records br
         JOIN   users u ON u.user_id = br.student_id
         JOIN   books b ON b.book_id = br.book_id
         WHERE  br.return_date IS NULL
           AND  br.status = \'approved\'
           AND  DATE_ADD(br.borrow_date, INTERVAL 14 DAY) < CURDATE()
         ORDER  BY overdue_days DESC
         LIMIT  50'
    )->fetchAll();
}

// ============================================================
//  Catalog — borrowed books (active + returned, with search)
// ============================================================
function fetch_borrowed_books(PDO $pdo, string $search = ''): array
{
    $sql = '
        SELECT
            br.borrow_id,
            u.full_name AS borrower,
            b.title,
            br.borrow_date,
            CASE
                WHEN br.return_date IS NOT NULL THEN \'Returned\'
                WHEN br.status = \'rejected\'   THEN \'Rejected\'
                WHEN br.status = \'pending\'    THEN \'Pending\'
                WHEN DATE_ADD(br.borrow_date, INTERVAL 14 DAY) < CURDATE() THEN \'Overdue\'
                ELSE \'Active\'
            END AS return_status
        FROM   borrow_records br
        JOIN   users u ON u.user_id = br.student_id
        JOIN   books b ON b.book_id = br.book_id';

    if ($search !== '') {
        $sql .= ' WHERE u.full_name LIKE :s OR b.title LIKE :s OR br.borrow_id LIKE :s';
    }

    $sql .= ' ORDER BY br.borrow_date DESC LIMIT 200';

    $stmt = $pdo->prepare($sql);
    if ($search !== '') {
        $stmt->bindValue(':s', '%' . $search . '%');
    }
    $stmt->execute();
    return $stmt->fetchAll();
}

// ============================================================
//  Books list with search
// ============================================================
function fetch_books(PDO $pdo, string $search = ''): array
{
    if ($search !== '') {
        $stmt = $pdo->prepare(
            'SELECT book_id, title, author, category, available_copies
             FROM   books
             WHERE  is_active = 1
               AND  (title LIKE :like OR author LIKE :like)
             ORDER  BY title LIMIT 200'
        );
        $stmt->execute([':like' => '%' . $search . '%']);
    } else {
        $stmt = $pdo->query(
            'SELECT book_id, title, author, category, available_copies
             FROM   books
             WHERE  is_active = 1
             ORDER  BY title LIMIT 200'
        );
    }
    return $stmt->fetchAll();
}
// ============================================================
//  Users (students) list with search
// ============================================================
function fetch_students(PDO $pdo, string $search = ''): array
{
    if ($search !== '') {
        $stmt = $pdo->prepare(
            'SELECT user_id, full_name, email, role, is_active, created_at
             FROM   users
             WHERE  (full_name LIKE :s OR email LIKE :s)
             ORDER  BY full_name
             LIMIT  200'
        );
        $stmt->bindValue(':s', '%' . $search . '%');
        $stmt->execute();
    } else {
        $stmt = $pdo->query(
            'SELECT user_id, full_name, email, role, is_active, created_at
             FROM   users
             ORDER  BY full_name
             LIMIT  200'
        );
    }
    return $stmt->fetchAll();
}

// ============================================================
//  Borrow a book — ACID transaction
//  Decrements available_copies atomically with the INSERT.
// ============================================================
function borrow_book(PDO $pdo, int $userId, int $bookId, int $daysAllowed = 14, ?int $createdBy = null): int
{
    return db_transaction(function (PDO $pdo) use ($userId, $bookId, $daysAllowed, $createdBy): int {
        // Lock the book row before reading available_copies
        $book = $pdo->prepare(
            'SELECT book_id, available_copies FROM books WHERE book_id = :id AND is_active = 1 FOR UPDATE'
        );
        $book->execute([':id' => $bookId]);
        $row = $book->fetch();

        if (!$row) {
            throw new RuntimeException('Book not found or inactive.');
        }
        if ((int) $row['available_copies'] < 1) {
            throw new RuntimeException('No copies currently available.');
        }

        // Insert borrow record
        $ins = $pdo->prepare(
            "INSERT INTO borrow_records (student_id, book_id, borrow_date, status)
             VALUES (:uid, :bid, CURDATE(), 'approved')"
        );
        $ins->execute([
            ':uid' => $userId,
            ':bid' => $bookId,
        ]);
        $borrowId = (int) $pdo->lastInsertId();

        // Decrement available copies
        $pdo->prepare('UPDATE books SET available_copies = available_copies - 1 WHERE book_id = :id')
            ->execute([':id' => $bookId]);

        audit('book.borrow', $borrowId, 'borrow_records', ['book_id' => $bookId, 'user_id' => $userId]);

        return $borrowId;
    });
}

// ============================================================
//  Return a book — ACID transaction
// ============================================================
function return_book(PDO $pdo, int $borrowId): void
{
    db_transaction(function (PDO $pdo) use ($borrowId): void {
        // Lock borrow record
        $stmt = $pdo->prepare(
            'SELECT borrow_id, book_id, status FROM borrow_records WHERE borrow_id = :id FOR UPDATE'
        );
        $stmt->execute([':id' => $borrowId]);
        $record = $stmt->fetch();

        if (!$record) {
            throw new RuntimeException('Borrow record not found.');
        }
        if ($record['status'] === 'returned') {
            throw new RuntimeException('Book has already been returned.');
        }

        // Mark as returned
        $pdo->prepare("UPDATE borrow_records SET status = 'returned', return_date = CURDATE() WHERE borrow_id = :id")
            ->execute([':id' => $borrowId]);

        // Restore available copies
        $pdo->prepare('UPDATE books SET available_copies = available_copies + 1 WHERE book_id = :id')
            ->execute([':id' => $record['book_id']]);

        audit('book.return', $borrowId, 'borrow_records', ['book_id' => $record['book_id']]);
    });
}

// ============================================================
//  Manage Books — all active books with current borrower
// ============================================================
function fetch_manage_books(PDO $pdo): array
{
    return $pdo->query(
        "SELECT b.book_id, b.title, b.author,
                b.category,
                b.available_copies,
                (SELECT u.full_name
                 FROM   borrow_records br
                 JOIN   users u ON u.user_id = br.student_id
                 WHERE  br.book_id = b.book_id AND br.status = 'approved'
                 AND    br.return_date IS NULL
                 LIMIT  1) AS current_borrower
         FROM   books b
         WHERE  b.is_active = 1
         ORDER  BY b.title"
    )->fetchAll();
}

// ============================================================
//  Book categories for dropdown
// ============================================================
function fetch_book_categories(PDO $pdo): array
{
    return $pdo->query(
        'SELECT DISTINCT category AS name FROM books WHERE category IS NOT NULL ORDER BY category'
    )->fetchAll();
}

// ============================================================
//  Borrow Requests — pending
// ============================================================
function fetch_pending_requests(PDO $pdo): array
{
    return $pdo->query(
        "SELECT br.borrow_id, u.full_name, u.email, b.title,
                br.borrow_date AS requested_date,
                DATE_ADD(br.borrow_date, INTERVAL 14 DAY) AS due_date
         FROM   borrow_records br
         JOIN   users u ON u.user_id = br.student_id
         JOIN   books b ON b.book_id = br.book_id
         WHERE  br.status = 'pending'
         ORDER  BY br.borrow_date ASC"
    )->fetchAll();
}

// ============================================================
//  Borrow Requests — all borrows
// ============================================================
function fetch_all_borrows(PDO $pdo): array
{
    return $pdo->query(
        "SELECT br.borrow_id, u.full_name, b.title, br.status,
                br.borrow_date,
                DATE_ADD(br.borrow_date, INTERVAL 14 DAY) AS due_date,
                br.return_date,
                CASE WHEN DATE_ADD(br.borrow_date, INTERVAL 14 DAY) < CURDATE()
                          AND br.status = 'approved'
                     THEN DATEDIFF(CURDATE(), DATE_ADD(br.borrow_date, INTERVAL 14 DAY))
                     ELSE NULL END AS overdue_days
         FROM   borrow_records br
         JOIN   users u ON u.user_id = br.student_id
         JOIN   books b ON b.book_id = br.book_id
         ORDER  BY br.borrow_date DESC
         LIMIT  200"
    )->fetchAll();
}