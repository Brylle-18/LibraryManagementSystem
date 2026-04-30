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
            (SELECT COUNT(*) FROM users  WHERE is_active = 1)          AS users,
            (SELECT COUNT(*) FROM books  WHERE is_active = 1)          AS books,
            (SELECT COUNT(*) FROM borrow_records WHERE returned_at IS NULL)      AS borrowed,
            (SELECT COUNT(*) FROM borrow_records WHERE returned_at IS NOT NULL)  AS returned'
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
        'SELECT user_id, full_name, email, title, overdue_days, due_date
         FROM   v_overdue_borrows
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
            u.full_name         AS borrower,
            b.title,
            DATE(br.borrowed_at) AS borrow_date,
            CASE
                WHEN br.returned_at IS NOT NULL THEN \'Returned\'
                WHEN br.due_date < CURDATE()    THEN \'Overdue\'
                ELSE \'Active\'
            END AS return_status
        FROM   borrow_records br
        JOIN   users u ON u.user_id = br.user_id
        JOIN   books b ON b.book_id = br.book_id';

    if ($search !== '') {
        $sql .= '
        WHERE  br.borrow_id   LIKE :s
            OR u.full_name    LIKE :s
            OR b.title        LIKE :s';
    }

    $sql .= ' ORDER BY br.borrowed_at DESC LIMIT 200';

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
        // Use FULLTEXT for speed on large catalogues, fall back to LIKE
        $stmt = $pdo->prepare(
            'SELECT b.book_id, b.title, b.author, c.name AS category,
                    b.available_copies, b.total_copies, b.isbn
             FROM   books b
             LEFT   JOIN book_categories c ON c.category_id = b.category_id
             WHERE  b.is_active = 1
               AND  (MATCH(b.title, b.author) AGAINST (:fts IN BOOLEAN MODE)
                     OR b.title  LIKE :like
                     OR b.author LIKE :like
                     OR b.isbn   LIKE :like)
             ORDER  BY b.title
             LIMIT  200'
        );
        $stmt->execute([':fts' => $search . '*', ':like' => '%' . $search . '%']);
    } else {
        $stmt = $pdo->query(
            'SELECT b.book_id, b.title, b.author, c.name AS category,
                    b.available_copies, b.total_copies, b.isbn
             FROM   books b
             LEFT   JOIN book_categories c ON c.category_id = b.category_id
             WHERE  b.is_active = 1
             ORDER  BY b.title
             LIMIT  200'
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
            'INSERT INTO borrow_records (user_id, book_id, borrowed_at, due_date, created_by)
             VALUES (:uid, :bid, NOW(), DATE_ADD(CURDATE(), INTERVAL :days DAY), :cby)'
        );
        $ins->execute([
            ':uid'  => $userId,
            ':bid'  => $bookId,
            ':days' => $daysAllowed,
            ':cby'  => $createdBy,
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
            'SELECT borrow_id, book_id, returned_at FROM borrow_records WHERE borrow_id = :id FOR UPDATE'
        );
        $stmt->execute([':id' => $borrowId]);
        $record = $stmt->fetch();

        if (!$record) {
            throw new RuntimeException('Borrow record not found.');
        }
        if ($record['returned_at'] !== null) {
            throw new RuntimeException('Book has already been returned.');
        }

        // Mark as returned
        $pdo->prepare('UPDATE borrow_records SET returned_at = NOW() WHERE borrow_id = :id')
            ->execute([':id' => $borrowId]);

        // Restore available copies
        $pdo->prepare('UPDATE books SET available_copies = available_copies + 1 WHERE book_id = :id')
            ->execute([':id' => $record['book_id']]);

        audit('book.return', $borrowId, 'borrow_records', ['book_id' => $record['book_id']]);
    });
}