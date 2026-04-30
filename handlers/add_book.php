<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_role(['admin', 'librarian']);

$title    = trim((string) ($_POST['title']        ?? ''));
$author   = trim((string) ($_POST['author']       ?? ''));
$category = trim((string) ($_POST['category']     ?? '')) ?: null;
$copies   = max(1, (int)  ($_POST['total_copies'] ?? 1));

if ($title === '' || $author === '') {
    set_flash('flash_error', 'Title and author are required.');
    redirect('../pages/dashboard.php?page=manage_books');
}

try {
    db()->prepare(
        'INSERT INTO books
            (category, title, author, available_copies, is_active)
         VALUES
            (:cat, :title, :author, :avail, 1)'
    )->execute([
        ':cat'    => $category,
        ':title'  => $title,
        ':author' => $author,
        ':avail'  => $copies,
    ]);

    set_flash('flash_success', '"' . $title . '" added to catalogue.');
} catch (PDOException $e) {
    set_flash('flash_error', 'Could not add book: ' . $e->getMessage());
}

redirect('../pages/dashboard.php?page=manage_books');
