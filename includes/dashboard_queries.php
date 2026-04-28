<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function fetch_dashboard_stats(PDO $pdo): array
{
    $stats = [
        'users' => (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
        'books' => (int) $pdo->query('SELECT COUNT(*) FROM books')->fetchColumn(),
        'borrowed' => (int) $pdo->query('SELECT COUNT(*) FROM borrow_records')->fetchColumn(),
        'returned' => (int) $pdo->query('SELECT COUNT(*) FROM borrow_records WHERE return_date IS NOT NULL')->fetchColumn(),
    ];

    return $stats;
}

function fetch_admins(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT full_name, email, role
         FROM users
         WHERE role IN ('admin', 'librarian')
         ORDER BY role, full_name"
    );

    return $stmt->fetchAll();
}

function fetch_overdue_borrowers(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT
            CONCAT(s.first_name, ' ', s.last_name) AS full_name,
            s.email,
            b.title,
            br.borrow_date,
            DATEDIFF(CURDATE(), br.borrow_date) AS overdue_days
         FROM borrow_records br
         INNER JOIN students s ON s.student_id = br.student_id
         INNER JOIN books b ON b.book_id = br.book_id
         WHERE br.return_date IS NULL
           AND br.borrow_date < DATE_SUB(CURDATE(), INTERVAL 14 DAY)
         ORDER BY br.borrow_date ASC"
    );

    return $stmt->fetchAll();
}

function fetch_borrowed_books(PDO $pdo, string $search = ''): array
{
    $sql = "SELECT
                br.borrow_id,
                CONCAT(s.first_name, ' ', s.last_name) AS borrower,
                b.title,
                br.borrow_date,
                COALESCE(DATE_FORMAT(br.return_date, '%Y-%m-%d'), 'Not returned') AS return_status
            FROM borrow_records br
            INNER JOIN students s ON s.student_id = br.student_id
            INNER JOIN books b ON b.book_id = br.book_id";

    $params = [];

    if ($search !== '') {
        $sql .= " WHERE br.borrow_id = :exact_id
                  OR s.student_id = :exact_student
                  OR b.book_id = :exact_book";
        $exactId = ctype_digit($search) ? (int) $search : 0;
        $params = [
            ':exact_id' => $exactId,
            ':exact_student' => $exactId,
            ':exact_book' => $exactId,
        ];
    }

    $sql .= ' ORDER BY br.borrow_date DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function fetch_books(PDO $pdo, string $search = ''): array
{
    $sql = "SELECT book_id, title, author, category, available_copies
            FROM books";
    $params = [];

    if ($search !== '') {
        $sql .= " WHERE title LIKE :term
                  OR author LIKE :term
                  OR category LIKE :term";
        $params[':term'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY title ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function fetch_students(PDO $pdo, string $search = ''): array
{
    $sql = "SELECT student_id, first_name, last_name, email, enrollment_date
            FROM students";
    $params = [];

    if ($search !== '') {
        $sql .= " WHERE first_name LIKE :term
                  OR last_name LIKE :term
                  OR email LIKE :term";
        $params[':term'] = '%' . $search . '%';
    }

    $sql .= ' ORDER BY first_name ASC, last_name ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}
