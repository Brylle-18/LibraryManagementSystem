<?php
declare(strict_types=1);

function fetch_my_active_borrows(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT br.borrow_id,
                b.title,
                b.author,
                br.borrow_date,
                DATE_ADD(br.borrow_date, INTERVAL 14 DAY) AS due_date,
                br.status,
                DATEDIFF(DATE_ADD(br.borrow_date, INTERVAL 14 DAY), CURDATE()) AS days_remaining
         FROM   borrow_records br
         JOIN   books b ON b.book_id = br.book_id
         WHERE  br.student_id = :uid
           AND  br.status IN (\'pending\', \'approved\')
         ORDER  BY br.borrow_date DESC'
    );
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll();
}

function fetch_all_books_with_status(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT b.book_id,
                b.title,
                b.author,
                b.category,
                b.available_copies,
                (SELECT status
                 FROM   borrow_records
                 WHERE  book_id    = b.book_id
                   AND  student_id = :uid
                   AND  status IN (\'pending\',\'approved\')
                 LIMIT  1) AS student_status
         FROM   books b
         ORDER  BY b.title'
    );
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll();
}

function fetch_my_borrow_history(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT br.borrow_id,
                b.title,
                b.author,
                br.borrow_date,
                DATE_ADD(br.borrow_date, INTERVAL 14 DAY) AS due_date,
                br.return_date,
                br.status
         FROM   borrow_records br
         JOIN   books b ON b.book_id = br.book_id
         WHERE  br.student_id = :uid
         ORDER  BY br.borrow_date DESC'
    );
    $stmt->execute([':uid' => $userId]);
    return $stmt->fetchAll();
}