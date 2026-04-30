<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/student_queries.php';

require_role('student');

// ── Handle borrow request POST ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_borrow'])) {
    $bookId = (int) ($_POST['book_id'] ?? 0);
    $uid    = (int) $_SESSION['user']['id'];

    try {
        db_transaction(function (PDO $pdo) use ($bookId, $uid): void {
            // Lock the book row
            $book = $pdo->prepare(
                'SELECT book_id, available_copies FROM books WHERE book_id = :id AND is_active = 1 FOR UPDATE'
            );
            $book->execute([':id' => $bookId]);
            $row = $book->fetch();

            if (!$row) {
                throw new RuntimeException('Book not found.');
            }
            if ((int) $row['available_copies'] < 1) {
                throw new RuntimeException('No copies available.');
            }

            // Check no active request already exists
            $existing = $pdo->prepare(
                "SELECT borrow_id FROM borrow_records
                 WHERE book_id = :bid AND student_id = :uid AND status IN ('pending','approved')
                 LIMIT 1"
            );
            $existing->execute([':bid' => $bookId, ':uid' => $uid]);
            if ($existing->fetch()) {
                throw new RuntimeException('You already have an active request for this book.');
            }

            // Insert as pending — available_copies unchanged until admin approves
            $pdo->prepare(
                "INSERT INTO borrow_records (student_id, book_id, borrow_date, status)
                 VALUES (:uid, :bid, CURDATE(), 'pending')"
            )->execute([':bid' => $bookId, ':uid' => $uid]);
        });

        set_flash('flash_success', 'Borrow request submitted. Awaiting admin approval.');
    } catch (Throwable $e) {
        $pageError = 'Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine();

    }

    redirect('userdashboard.php?page=browse');
}

// ── Page data ────────────────────────────────────────────
$activePage = $_GET['page'] ?? 'my_books';
$user       = $_SESSION['user'];
$userId     = (int) $user['id'];

$flashError   = get_flash('flash_error');
$flashSuccess = get_flash('flash_success');

$myBorrows = [];
$allBooks  = [];
$myHistory = [];
$pageError = null;

try {
    $pdo       = db();
    $myBorrows = fetch_my_active_borrows($pdo, $userId);
    $allBooks  = fetch_all_books_with_status($pdo, $userId);
    $myHistory = fetch_my_borrow_history($pdo, $userId);
} catch (PDOException $e) {
    $pageError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libraread — My Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student.css">
</head>
<body>
<div class="whole-dashboard">

    <!-- ── Sidebar ─────────────────────────────────────── -->
    <div class="left-bar">
        <div class="navbar">
            <div class="brand">
                <h1>Libraread</h1>
                <span class="brand-sub">Student Portal</span>
            </div>
            <div class="menu">
                <ul>
                    <li>
                        <a href="?page=my_books" class="<?= $activePage === 'my_books' ? 'active' : '' ?>">
                            <i class="fa-solid fa-book-open"></i> My Books
                        </a>
                    </li>
                    <li>
                        <a href="?page=browse" class="<?= $activePage === 'browse' ? 'active' : '' ?>">
                            <i class="fa-solid fa-magnifying-glass"></i> Browse Books
                        </a>
                    </li>
                    <li>
                        <a href="?page=history" class="<?= $activePage === 'history' ? 'active' : '' ?>">
                            <i class="fa-solid fa-clock-rotate-left"></i> My History
                        </a>
                    </li>
                </ul>
            </div>
            <div class="logout">
                <a href="../auth/logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i> Log Out
                </a>
            </div>
        </div>
    </div>

    <!-- ── Main content ────────────────────────────────── -->
    <div class="main-content">

        <!-- Topbar -->
        <div class="topbar">
            <div class="topbar-user">
                <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
                <div>
                    <span class="user-name"><?= h($user['name']) ?></span>
                    <span class="user-role"><?= h(ucfirst($user['role'])) ?></span>
                </div>
            </div>
            <div class="topbar-time" id="topbar-time"></div>
        </div>

        <?php if ($pageError !== null): ?>
            <div class="page-content">
                <div class="flash-banner error"><i class="fa-solid fa-circle-exclamation"></i> <?= h($pageError) ?></div>
            </div>
        <?php endif; ?>

        <!-- ══ MY BOOKS ══════════════════════════════════ -->
        <?php if ($activePage === 'my_books'): ?>
        <div class="page-content">
            <h1 class="page-title">My Books</h1>
            <p class="page-subtitle">Your current and pending borrows.</p>

            <?php if ($flashSuccess): ?>
                <div class="flash-banner success"><i class="fa-solid fa-circle-check"></i> <?= h($flashSuccess) ?></div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="flash-banner error"><i class="fa-solid fa-circle-exclamation"></i> <?= h($flashError) ?></div>
            <?php endif; ?>

            <?php if ($myBorrows === []): ?>
                <div class="table-wrapper">
                    <div class="empty-state">
                        <i class="fa-solid fa-book-open"></i>
                        <p>You have no active borrows. <a href="?page=browse" style="color:var(--ink);font-weight:600;">Browse books</a> to request one.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th>Requested Date</th>
                                <th>Due Date</th>
                                <th>Days Remaining</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($myBorrows as $row): ?>
                            <tr>
                                <td><?= h($row['title']) ?></td>
                                <td><?= h($row['author']) ?></td>
                                <td><?= h((string) $row['borrow_date']) ?></td>
                                <td><?= h((string) $row['due_date']) ?></td>
                                <td>
                                    <?php
                                    $days = (int) $row['days_remaining'];
                                    if ($row['status'] === 'pending'):
                                    ?><span class="days-neutral">Awaiting approval</span><?php
                                    elseif ($days > 3):
                                    ?><span class="days-ok"><?= $days ?> days left</span><?php
                                    elseif ($days >= 0):
                                    ?><span class="days-warning"><?= $days ?> days left</span><?php
                                    else:
                                    ?><span class="days-overdue"><?= abs($days) ?> days overdue</span><?php
                                    endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'pending'): ?>
                                        <span class="badge badge-pending">Pending</span>
                                    <?php elseif ($row['status'] === 'approved' && (int)$row['days_remaining'] < 0): ?>
                                        <span class="badge badge-overdue"><i class="fa-solid fa-triangle-exclamation"></i> Overdue</span>
                                    <?php else: ?>
                                        <span class="badge badge-approved">Approved</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- ══ BROWSE BOOKS ══════════════════════════════ -->
        <?php elseif ($activePage === 'browse'): ?>
        <div class="page-content">
            <h1 class="page-title">Browse Books</h1>
            <p class="page-subtitle">Find and request books from the catalogue.</p>

            <?php if ($flashSuccess): ?>
                <div class="flash-banner success"><i class="fa-solid fa-circle-check"></i> <?= h($flashSuccess) ?></div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="flash-banner error"><i class="fa-solid fa-circle-exclamation"></i> <?= h($flashError) ?></div>
            <?php endif; ?>

            <?php if ($allBooks === []): ?>
                <div class="table-wrapper">
                    <div class="empty-state">
                        <i class="fa-solid fa-books"></i>
                        <p>No books in the catalogue yet.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Available</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($allBooks as $book): ?>
                            <tr>
                                <td><?= h($book['title']) ?></td>
                                <td><?= h($book['author']) ?></td>
                                <td><?= h($book['category'] ?? '—') ?></td>
                             <td><?= h((string) $book['available_copies']) ?></td>
                                    <td>
                                    <?php if ((int) $book['available_copies'] < 1): ?>
                                        <span class="badge badge-unavailable">Not Available</span>
                                    <?php else: ?>
                                        <span class="badge badge-available">Available</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int) $book['available_copies'] < 1): ?>
                                        <span class="badge badge-unavailable">Not Available</span>
                                    <?php elseif ($book['student_status'] !== null): ?>
                                        <span class="badge badge-pending">Already Requested</span>
                                    <?php else: ?>
                                        <form method="post" action="userdashboard.php?page=browse">
                                            <input type="hidden" name="book_id" value="<?= (int) $book['book_id'] ?>">
                                            <input type="hidden" name="request_borrow" value="1">
                                            <button type="submit" class="btn-primary">
                                                <i class="fa-solid fa-plus"></i> Request Borrow
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- ══ MY HISTORY ════════════════════════════════ -->
        <?php elseif ($activePage === 'history'): ?>
        <div class="page-content">
            <h1 class="page-title">My History</h1>
            <p class="page-subtitle">All your borrow records.</p>

            <?php if ($myHistory === []): ?>
                <div class="table-wrapper">
                    <div class="empty-state">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <p>No borrow history yet.</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Book Title</th>
                                <th>Author</th>
                                <th>Requested</th>
                                <th>Due Date</th>
                                <th>Returned</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($myHistory as $row): ?>
                            <tr>
                                <td><?= h($row['title']) ?></td>
                                <td><?= h($row['author']) ?></td>
                                <td><?= h((string) $row['borrow_date']) ?></td>
                                <td><?= h((string) $row['due_date']) ?></td>
                                <td><?= $row['return_date'] ? h((string) $row['return_date']) : '—' ?></td>
                                <td>
                                    <?php $s = $row['status']; ?>
                                    <span class="badge badge-<?= h($s) ?>"><?= h(ucfirst($s)) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div><!-- /main-content -->
</div><!-- /whole-dashboard -->

<script>
(function tick() {
    const el = document.getElementById('topbar-time');
    if (el) {
        const now = new Date();
        el.textContent = now.toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric' })
            + '  ' + now.toLocaleTimeString('en-US', { hour:'2-digit', minute:'2-digit' });
    }
    setTimeout(tick, 1000);
})();
</script>
</body>
</html>
