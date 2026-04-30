<?php

declare(strict_types=1);

// pages/ is one level deep — go up with /../
require_once __DIR__ . '/../includes/dashboard_queries.php';

require_login();

$activePage    = $_GET['page']          ?? 'dashboard';
$activeTab     = $_GET['catalog_tab']   ?? 'borrowed';
$borrowTab     = $_GET['borrow_tab']    ?? 'pending';
$catalogSearch = trim((string) ($_GET['catalog_search'] ?? ''));
$booksSearch   = trim((string) ($_GET['books_search']   ?? ''));
$usersSearch   = trim((string) ($_GET['users_search']   ?? ''));

$flashSuccess = get_flash('flash_success');
$flashError   = get_flash('flash_error');

$dashboardError  = null;
$stats           = ['users' => 0, 'books' => 0, 'borrowed' => 0, 'returned' => 0];
$admins          = [];
$overdueBorrowers = [];
$borrowedBooks   = [];
$books           = [];
$students        = [];
$manageBooks     = [];
$bookCategories  = [];
$pendingRequests = [];
$allBorrows      = [];

try {
    $pdo              = db();
    $stats            = fetch_dashboard_stats($pdo);
    $admins           = fetch_admins($pdo);
    $overdueBorrowers = fetch_overdue_borrowers($pdo);
    $borrowedBooks    = fetch_borrowed_books($pdo, $catalogSearch);
    $books            = fetch_books($pdo, $booksSearch);
    $students         = fetch_students($pdo, $usersSearch);
    $manageBooks      = fetch_manage_books($pdo);
    $bookCategories   = fetch_book_categories($pdo);
    $pendingRequests  = fetch_pending_requests($pdo);
    $allBorrows       = fetch_all_borrows($pdo);
} catch (PDOException $exception) {
    $dashboardError = 'Database connection failed. Import `database/library.sql` and make sure MySQL is running.';
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libraread — Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- ↑ CDN is absolute, no path fix needed -->
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body data-active-page="<?= h($activePage) ?>" data-active-tab="<?= h($activeTab) ?>">
    <div class="whole-dashboard">

        <!-- ── Sidebar ─────────────────────────────────────── -->
        <div class="left-bar">
            <div class="navbar">
                <div class="brand">
                    <div class="brand-icon">
                        <i class="fa-solid fa-book-open"></i>
                    </div>
                    <h1>Libraread</h1>
                    <span class="brand-sub">LIBRARY</span>
                </div>
                <div class="menu">
                    <ul>
                        <li>
                            <a href="?page=dashboard"
                               class="<?= $activePage === 'dashboard' ? 'active' : '' ?>"
                               data-page="dashboard">
                                <i class="fa-solid fa-gauge-high"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="?page=catalog&catalog_tab=borrowed"
                               class="<?= $activePage === 'catalog' ? 'active' : '' ?>"
                               data-page="catalog">
                                <i class="fa-solid fa-book-bookmark"></i> Catalog
                            </a>
                        </li>
                        <li>
                            <a href="?page=books"
                               class="<?= $activePage === 'books' ? 'active' : '' ?>"
                               data-page="books">
                                <i class="fa-solid fa-book-open-reader"></i> Books
                            </a>
                        </li>
                        <li>
                            <a href="?page=users"
                               class="<?= $activePage === 'users' ? 'active' : '' ?>"
                               data-page="users">
                                <i class="fa-solid fa-users"></i> Users
                            </a>
                        </li>
                        <li>
                            <a href="?page=manage_books"
                               class="<?= $activePage === 'manage_books' ? 'active' : '' ?>"
                               data-page="manage_books">
                                <i class="fa-solid fa-screwdriver-wrench"></i> Manage Books
                            </a>
                        </li>
                        <li>
                            <a href="?page=borrow_requests"
                               class="<?= $activePage === 'borrow_requests' ? 'active' : '' ?>"
                               data-page="borrow_requests">
                                <i class="fa-solid fa-clock"></i> Borrow Requests
                                <?php if (count($pendingRequests) > 0): ?>
                                    <span style="margin-left:auto;background:#fff;color:#0f0f0f;font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;"><?= count($pendingRequests) ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="logout">
                    <!-- auth/logout.php is at root/auth/, go up one level -->
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
                    <div class="user-info">
                        <span class="user-name"><?= h($user['name']) ?></span>
                        <span class="user-role"><?= h(ucfirst($user['role'])) ?></span>
                    </div>
                </div>
                <div class="topbar-right">
                    <div class="topbar-time" id="topbar-time"></div>
                </div>
            </div>

            <?php if ($dashboardError !== null): ?>
                <div class="empty-table-state" style="margin-bottom:20px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <p><?= h($dashboardError) ?></p>
                </div>
            <?php endif; ?>

            <?php if ($flashSuccess !== null): ?>
                <div style="margin:16px 28px 0;padding:12px 16px;background:#f4fff8;border:1px solid #b2dfc5;color:#1a5e35;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:10px;">
                    <i class="fa-solid fa-circle-check"></i> <?= h($flashSuccess) ?>
                </div>
            <?php endif; ?>
            <?php if ($flashError !== null): ?>
                <div style="margin:16px 28px 0;padding:12px 16px;background:#fff4f4;border:1px solid #ffc6c6;color:#8a2222;border-radius:8px;font-size:13px;display:flex;align-items:center;gap:10px;">
                    <i class="fa-solid fa-circle-exclamation"></i> <?= h($flashError) ?>
                </div>
            <?php endif; ?>

            <!-- ══ DASHBOARD PAGE ══════════════════════════════ -->
            <div class="page <?= $activePage === 'dashboard' ? 'active' : '' ?>" id="page-dashboard">
                <div class="dashboard-grid">

                    <!-- Borrow chart -->
                    <div class="dash-chart-area">
                        <canvas id="borrowChart"
                            width="320"
                            height="320"
                            data-borrowed="<?= (int) $stats['borrowed'] ?>"
                            data-returned="<?= (int) $stats['returned'] ?>"></canvas>
                        <div class="chart-legend">
                            <span class="legend-dot dark"></span> Total Borrowed Books
                            <span class="legend-dot mid"></span>  Total Returned Books
                        </div>
                    </div>

                    <!-- Stat cards -->
                    <div class="dash-stats">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa-solid fa-user"></i></div>
                            <div class="stat-divider"></div>
                            <div class="stat-info">
                                <span class="stat-number" id="stat-users">
                                    <?= str_pad((string) $stats['users'], 4, '0', STR_PAD_LEFT) ?>
                                </span>
                                <span class="stat-label">Total User Base</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa-solid fa-book-open-reader"></i></div>
                            <div class="stat-divider"></div>
                            <div class="stat-info">
                                <span class="stat-number" id="stat-books">
                                    <?= str_pad((string) $stats['books'], 4, '0', STR_PAD_LEFT) ?>
                                </span>
                                <span class="stat-label">Total Book Count</span>
                            </div>
                        </div>
                    </div>

                    <!-- Overdue borrowers panel -->
                    <div class="dash-panel">
                        <h3 class="panel-title">Overdue Borrowers</h3>
                        <div class="panel-list" id="overdue-list">
                            <?php if ($overdueBorrowers === []): ?>
                                <div class="empty-state">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <p>No overdue borrowers</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($overdueBorrowers as $row): ?>
                                    <div class="person-row">
                                        <div class="person-icon"><i class="fa-solid fa-user"></i></div>
                                        <div class="person-details">
                                            <div class="person-name"><?= h($row['full_name']) ?></div>
                                            <div class="person-sub"><?= h($row['title']) ?></div>
                                            <span><small><?= h((string) $row['overdue_days']) ?> days overdue</small></span>
                                        </div>
                                        <i class="fa-solid fa-clock person-action"></i>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Admins panel -->
                    <div class="dash-admins">
                        <h3 class="panel-title">Libraread Admins</h3>
                        <div class="panel-list" id="admin-list">
                            <?php if ($admins === []): ?>
                                <div class="empty-state">
                                    <i class="fa-solid fa-user-shield"></i>
                                    <p>No admins found</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($admins as $admin): ?>
                                    <div class="person-row">
                                        <div class="person-icon"><i class="fa-solid fa-user-shield"></i></div>
                                        <div class="person-details">
                                            <div class="person-name"><?= h($admin['full_name']) ?></div>
                                            <div class="person-sub"><?= h($admin['email']) ?></div>
                                            <span>
                                                <span class="status-dot"></span>
                                                <small><?= h(ucfirst($admin['role'])) ?></small>
                                            </span>
                                        </div>
                                        <i class="fa-solid fa-user-gear person-action"></i>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ══ CATALOG PAGE ════════════════════════════════ -->
            <div class="page <?= $activePage === 'catalog' ? 'active' : '' ?>" id="page-catalog">
                <div class="page-header">
                    <div class="tab-group">
                        <a class="tab <?= $activeTab === 'borrowed' ? 'active' : '' ?>"
                           href="?page=catalog&catalog_tab=borrowed&catalog_search=<?= urlencode($catalogSearch) ?>"
                           data-tab="borrowed">Borrowed Books</a>
                        <a class="tab <?= $activeTab === 'overdue' ? 'active' : '' ?>"
                           href="?page=catalog&catalog_tab=overdue&catalog_search=<?= urlencode($catalogSearch) ?>"
                           data-tab="overdue">Overdue Borrowers</a>
                    </div>
                    <form class="search-bar" method="get" action="dashboard.php">
                        <input type="hidden" name="page" value="catalog">
                        <input type="hidden" name="catalog_tab" value="<?= h($activeTab) ?>">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text"
                               placeholder="Search by record, borrower, or book"
                               id="catalog-search"
                               name="catalog_search"
                               value="<?= h($catalogSearch) ?>">
                    </form>
                </div>

                <!-- Borrowed tab -->
                <div class="tab-content <?= $activeTab === 'borrowed' ? 'active' : '' ?>" id="tab-borrowed">
                    <div class="table-wrapper" id="borrowed-table-wrapper">
                        <?php if ($borrowedBooks === []): ?>
                            <div class="empty-table-state">
                                <i class="fa-solid fa-book-open"></i>
                                <p>No borrowed book records found.</p>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Borrow ID</th>
                                        <th>Borrower</th>
                                        <th>Book Title</th>
                                        <th>Borrow Date</th>
                                        <th>Return Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($borrowedBooks as $row): ?>
                                        <tr>
                                            <td><?= h((string) $row['borrow_id']) ?></td>
                                            <td><?= h($row['borrower']) ?></td>
                                            <td><?= h($row['title']) ?></td>
                                            <td><?= h($row['borrow_date']) ?></td>
                                            <td><?= h($row['return_status']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Overdue tab -->
                <div class="tab-content <?= $activeTab === 'overdue' ? 'active' : '' ?>" id="tab-overdue">
                    <div class="table-wrapper" id="overdue-table-wrapper">
                        <?php if ($overdueBorrowers === []): ?>
                            <div class="empty-table-state">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <p>No overdue borrowers found.</p>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Borrower</th>
                                        <th>Email</th>
                                        <th>Book</th>
                                        <th>Borrow Date</th>
                                        <th>Overdue Days</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($overdueBorrowers as $row): ?>
                                        <tr>
                                            <td><?= h($row['full_name']) ?></td>
                                            <td><?= h($row['email']) ?></td>
                                            <td><?= h($row['title']) ?></td>
                                            <td><?= h($row['borrow_date']) ?></td>
                                            <td><?= h((string) $row['overdue_days']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ══ BOOKS PAGE ══════════════════════════════════ -->
            <div class="page <?= $activePage === 'books' ? 'active' : '' ?>" id="page-books">
                <div class="page-header">
                    <form class="search-bar" method="get" action="dashboard.php">
                        <input type="hidden" name="page" value="books">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text"
                               placeholder="Search by title, author, or ISBN..."
                               id="books-search"
                               name="books_search"
                               value="<?= h($booksSearch) ?>">
                    </form>
                </div>
                <div class="table-wrapper" id="books-table-wrapper">
                    <?php if ($books === []): ?>
                        <div class="empty-table-state">
                            <i class="fa-solid fa-books"></i>
                            <p>No books found.</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Book ID</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Available Copies</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td><?= h((string) $book['book_id']) ?></td>
                                        <td><?= h($book['title']) ?></td>
                                        <td><?= h($book['author']) ?></td>
                                        <td><?= h($book['category'] ?? '—') ?></td>
                                        <td><?= h((string) $book['available_copies']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ══ USERS PAGE ══════════════════════════════════ -->
            <div class="page <?= $activePage === 'users' ? 'active' : '' ?>" id="page-users">
                <div class="page-header">
                    <form class="search-bar" method="get" action="dashboard.php">
                        <input type="hidden" name="page" value="users">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text"
                               placeholder="Search by name or email..."
                               id="users-search"
                               name="users_search"
                               value="<?= h($usersSearch) ?>">
                    </form>
                </div>
                <div class="table-wrapper" id="users-table-wrapper">
                    <?php if ($students === []): ?>
                        <div class="empty-table-state">
                            <i class="fa-solid fa-users"></i>
                            <p>No users registered yet.</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>User ID</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?= h((string) $student['user_id']) ?></td>
                                        <td><?= h($student['full_name']) ?></td>
                                        <td><?= h($student['email']) ?></td>
                                        <td><?= h(ucfirst($student['role'])) ?></td>
                                        <td><?= h($student['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>


            <!-- ====================== MANAGE BOOKS ====================== -->
            <div id="page-manage_books" class="page-section<?= $activePage === 'manage_books' ? ' active' : '' ?>">

                <!-- Page header -->
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px;margin:10px;">
                    <div>
                        <h2 style="font-family:'DM Serif Display',serif;font-size:1.75rem;font-weight:400;letter-spacing:-0.5px;margin-bottom:4px;">Book Catalogue</h2>
                        <p style="font-size:13px;color:var(--ink-light);"><?= count($manageBooks) ?> book<?= count($manageBooks) !== 1 ? 's' : '' ?> in the library</p>
                    </div>
                    <button onclick="document.getElementById('addBookModal').style.display='flex'"
                            style="display:inline-flex;align-items:center;gap:8px;background:var(--ink);color:#fff;border:none;border-radius:8px;padding:10px 18px;font-size:14px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;transition:opacity .15s;"
                            onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
                        <i class="fa-solid fa-plus"></i> Add Book
                    </button>
                </div>

                <?php if (empty($manageBooks)): ?>
                    <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;padding:72px 24px;text-align:center;color:var(--ink-light);">
                        <i class="fa-solid fa-book-open" style="font-size:36px;margin-bottom:12px;display:block;opacity:.3;"></i>
                        <p style="font-size:15px;font-weight:500;color:#999;">No books yet</p>
                        <p style="font-size:13px;color:#bbb;margin-top:4px;">Click "Add Book" to add your first title.</p>
                    </div>
                <?php else: ?>
                    <div style="background:var(--white);border:1px solid var(--border);border-radius:14px;overflow:hidden;">
                        <table style="width:100%;border-collapse:collapse;font-size:13.5px;">
                            <thead>
                                <tr style="border-bottom:1px solid var(--border);background:var(--surface);">
                                    <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:700;color:var(--ink-light);text-transform:uppercase;letter-spacing:.6px;white-space:nowrap;">#</th>
                                    <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:700;color:var(--ink-light);text-transform:uppercase;letter-spacing:.6px;">Title</th>
                                    <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:700;color:var(--ink-light);text-transform:uppercase;letter-spacing:.6px;">Author</th>
                                    <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:700;color:var(--ink-light);text-transform:uppercase;letter-spacing:.6px;">Category</th>
                                    <th style="padding:12px 18px;text-align:center;font-size:11px;font-weight:700;color:var(--ink-light);text-transform:uppercase;letter-spacing:.6px;">Copies</th>
                                    <th style="padding:12px 18px;text-align:left;font-size:11px;font-weight:700;color:var(--ink-light);text-transform:uppercase;letter-spacing:.6px;">Borrowed By</th>
                                    <th style="padding:12px 18px;text-align:center;font-size:11px;font-weight:700;color:var(--ink-light);text-transform:uppercase;letter-spacing:.6px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($manageBooks as $mb): ?>
                                    <tr style="border-bottom:1px solid #f4f3f0;transition:background .1s;" onmouseover="this.style.background='var(--surface)'" onmouseout="this.style.background=''">
                                        <td style="padding:14px 18px;color:var(--ink-light);font-size:12px;"><?= h((string) $mb['book_id']) ?></td>
                                        <td style="padding:14px 18px;">
                                            <span style="font-weight:600;color:var(--ink);"><?= h($mb['title']) ?></span>
                                        </td>
                                        <td style="padding:14px 18px;color:var(--ink-mid);"><?= h($mb['author']) ?></td>
                                        <td style="padding:14px 18px;">
                                            <?php if ($mb['category']): ?>
                                                <span style="display:inline-block;padding:3px 10px;background:#f0efeb;border-radius:999px;font-size:12px;color:var(--ink-mid);font-weight:500;"><?= h($mb['category']) ?></span>
                                            <?php else: ?>
                                                <span style="color:var(--ink-light);">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding:14px 18px;text-align:center;">
                                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:13px;font-weight:600;color:<?= (int)$mb['available_copies'] > 0 ? 'var(--ink)' : 'var(--ink-light)' ?>;">
                                                <i class="fa-solid fa-copy" style="font-size:11px;opacity:.5;"></i>
                                                <?= h((string) $mb['available_copies']) ?>
                                            </span>
                                        </td>
                                        <td style="padding:14px 18px;">
                                            <?php if ($mb['current_borrower']): ?>
                                                <span style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--ink-mid);">
                                                    <i class="fa-solid fa-user" style="font-size:11px;color:var(--ink-light);"></i>
                                                    <?= h($mb['current_borrower']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color:var(--ink-light);font-size:13px;">Available</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding:14px 18px;text-align:center;">
                                            <form method="POST" action="../handlers/delete_book.php"
                                                  onsubmit="return confirm('Deactivate «<?= h(addslashes($mb['title'])) ?>»?')">
                                            <?= csrf_field() ?>
                                                <input type="hidden" name="book_id" value="<?= h((string) $mb['book_id']) ?>">
                                                <button type="submit"
                                                        style="display:inline-flex;align-items:center;gap:5px;background:none;border:1px solid var(--border);border-radius:7px;padding:6px 12px;font-size:12px;font-weight:600;color:var(--ink-mid);cursor:pointer;font-family:'DM Sans',sans-serif;transition:background .15s,color .15s;"
                                                        onmouseover="this.style.background='var(--ink)';this.style.color='#fff';this.style.borderColor='var(--ink)'"
                                                        onmouseout="this.style.background='';this.style.color='var(--ink-mid)';this.style.borderColor='var(--border)'">
                                                    <i class="fa-solid fa-trash-can" style="font-size:11px;"></i> Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div><!-- /page-manage_books -->

            <!-- ── Add Book Modal ─────────────────────────────────────── -->
            <div id="addBookModal"
                 style="display:none;position:fixed;inset:0;background:rgba(15,15,15,.45);z-index:1000;align-items:center;justify-content:center;padding:20px;"
                 onclick="if(event.target===this)this.style.display='none'">
                <div style="background:#fff;border-radius:16px;width:520px;max-width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.2);position:relative;">

                    <!-- Modal header -->
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:24px 28px 0;">
                        <div>
                            <h3 style="font-family:'DM Serif Display',serif;font-size:1.4rem;font-weight:400;letter-spacing:-0.3px;">Add New Book</h3>
                            <p style="font-size:13px;color:var(--ink-light);margin-top:2px;">Fill in the details to add a title to the catalogue.</p>
                        </div>
                        <button onclick="document.getElementById('addBookModal').style.display='none'"
                                style="background:var(--surface);border:none;border-radius:8px;width:34px;height:34px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;color:var(--ink-mid);flex-shrink:0;"
                                onmouseover="this.style.background='#eee'" onmouseout="this.style.background='var(--surface)'">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div style="height:1px;background:var(--border);margin:18px 0;"></div>

                    <!-- Modal body -->
                    <form method="POST" action="../handlers/add_book.php" style="padding:0 28px 28px;">
                        <?= csrf_field() ?>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">

                            <div style="grid-column:1/-1;">
                                <label style="display:block;font-size:12px;font-weight:700;color:var(--ink-mid);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                                    Title <span style="color:#c00;">*</span>
                                </label>
                                <input type="text" name="title" required placeholder="e.g. Noli Me Tangere"
                                       style="width:100%;padding:10px 13px;border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--ink);background:#fff;transition:border .15s;outline:none;"
                                       onfocus="this.style.borderColor='var(--ink)'" onblur="this.style.borderColor='var(--border)'">
                            </div>

                            <div style="grid-column:1/-1;">
                                <label style="display:block;font-size:12px;font-weight:700;color:var(--ink-mid);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                                    Author <span style="color:#c00;">*</span>
                                </label>
                                <input type="text" name="author" required placeholder="e.g. José Rizal"
                                       style="width:100%;padding:10px 13px;border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--ink);background:#fff;transition:border .15s;outline:none;"
                                       onfocus="this.style.borderColor='var(--ink)'" onblur="this.style.borderColor='var(--border)'">
                            </div>

                            <div>
                                <label style="display:block;font-size:12px;font-weight:700;color:var(--ink-mid);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Category</label>
                                <input type="text" name="category" placeholder="e.g. Fiction, Science"
                                       style="width:100%;padding:10px 13px;border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--ink);background:#fff;transition:border .15s;outline:none;"
                                       onfocus="this.style.borderColor='var(--ink)'" onblur="this.style.borderColor='var(--border)'">
                            </div>

                            <div>
                                <label style="display:block;font-size:12px;font-weight:700;color:var(--ink-mid);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                                    Copies <span style="color:#c00;">*</span>
                                </label>
                                <input type="number" name="total_copies" min="1" value="1" required
                                       style="width:100%;padding:10px 13px;border:1px solid var(--border);border-radius:8px;font-size:14px;font-family:'DM Sans',sans-serif;color:var(--ink);background:#fff;transition:border .15s;outline:none;"
                                       onfocus="this.style.borderColor='var(--ink)'" onblur="this.style.borderColor='var(--border)'">
                            </div>

                        </div>

                        <div style="margin-top:24px;display:flex;gap:10px;">
                            <button type="button"
                                    onclick="document.getElementById('addBookModal').style.display='none'"
                                    style="flex:1;padding:11px;border:1px solid var(--border);border-radius:8px;background:#fff;font-size:14px;font-weight:600;color:var(--ink-mid);cursor:pointer;font-family:'DM Sans',sans-serif;">
                                Cancel
                            </button>
                            <button type="submit"
                                    style="flex:2;padding:11px;border:none;border-radius:8px;background:var(--ink);color:#fff;font-size:14px;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;display:inline-flex;align-items:center;justify-content:center;gap:8px;transition:opacity .15s;"
                                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                                <i class="fa-solid fa-plus"></i> Add to Catalogue
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            <!-- ====================== BORROW REQUESTS ====================== -->
            <div id="page-borrow_requests" class="page-section<?= $activePage === 'borrow_requests' ? ' active' : '' ?>">

                <h2 style="font-family:'DM Serif Display',serif;font-size:1.5rem;margin-bottom:20px;margin:10px;">Borrow Requests</h2>

                <!-- Tabs -->
                <div style="display:flex;gap:0;border-bottom:2px solid #e0ddd8;margin-bottom:24px;">
                    <a href="?page=borrow_requests&borrow_tab=pending"
                       style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;
                              border-bottom:<?= $borrowTab === 'pending' ? '3px solid #0f0f0f' : '3px solid transparent' ?>;
                              color:<?= $borrowTab === 'pending' ? '#0f0f0f' : '#888' ?>">
                        Pending
                        <?php if (count($pendingRequests) > 0): ?>
                            <span style="background:#0f0f0f;color:#fff;font-size:11px;padding:1px 8px;border-radius:20px;margin-left:6px;"><?= count($pendingRequests) ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?page=borrow_requests&borrow_tab=all"
                       style="padding:10px 20px;font-size:14px;font-weight:600;text-decoration:none;
                              border-bottom:<?= $borrowTab === 'all' ? '3px solid #0f0f0f' : '3px solid transparent' ?>;
                              color:<?= $borrowTab === 'all' ? '#0f0f0f' : '#888' ?>">
                        All Borrows
                    </a>
                </div>

                <?php if ($borrowTab === 'pending'): ?>
                    <?php if (empty($pendingRequests)): ?>
                        <div class="empty-table-state"><i class="fa-solid fa-check-circle"></i><p>No pending requests.</p></div>
                    <?php else: ?>
                        <div class="catalog-table-wrapper">
                            <table class="catalog-table">
                                <thead>
                                    <tr>
                                        <th>Borrower</th>
                                        <th>Email</th>
                                        <th>Book</th>
                                        <th>Requested</th>
                                        <th>Due Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingRequests as $req): ?>
                                        <tr>
                                            <td><?= h($req['full_name']) ?></td>
                                            <td><?= h($req['email']) ?></td>
                                            <td><?= h($req['title']) ?></td>
                                            <td><?= h($req['requested_date']) ?></td>
                                            <td><?= h((string) $req['due_date']) ?></td>
                                            <td style="display:flex;gap:8px;flex-wrap:wrap;">
                                                <form method="POST" action="../handlers/approve_borrow.php">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="borrow_id" value="<?= h((string) $req['borrow_id']) ?>">
                                                    <button type="submit" class="btn-primary" style="font-size:13px;padding:6px 14px;">
                                                        <i class="fa-solid fa-check"></i> Approve
                                                    </button>
                                                </form>
                                                <form method="POST" action="../handlers/reject_borrow.php">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="borrow_id" value="<?= h((string) $req['borrow_id']) ?>">
                                                    <button type="submit" class="btn-outline" style="font-size:13px;padding:6px 14px;">
                                                        <i class="fa-solid fa-xmark"></i> Reject
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                <?php else: // all borrows tab ?>
                    <?php if (empty($allBorrows)): ?>
                        <div class="empty-table-state"><i class="fa-solid fa-book"></i><p>No borrow records yet.</p></div>
                    <?php else: ?>
                        <div class="catalog-table-wrapper all-borrows-wrapper">
                            <table class="catalog-table all-borrows-table">
                                <thead>
                                    <tr>
                                        <th>Borrower</th>
                                        <th>Book</th>
                                        <th>Status</th>
                                        <th>Borrowed</th>
                                        <th>Due</th>
                                        <th>Returned</th>
                                        <th>Overdue Days</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allBorrows as $ab): ?>
                                        <tr>
                                            <td><?= h($ab['full_name']) ?></td>
                                            <td><?= h($ab['title']) ?></td>
                                            <td>
                                                <?php
                                                $statusColors = [
                                                    'pending'  => '#888',
                                                    'approved' => '#0f0f0f',
                                                    'rejected' => '#aaa',
                                                    'returned' => '#555',
                                                ];
                                                $sc = $statusColors[$ab['status']] ?? '#555';
                                                ?>
                                                <span style="font-weight:700;color:<?= $sc ?>;">
                                                    <?= h(ucfirst($ab['status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= h($ab['borrow_date']) ?></td>
                                            <td><?= h((string) $ab['due_date']) ?></td>
                                            <td><?= $ab['return_date'] ? h($ab['return_date']) : '—' ?></td>
                                            <td><?= $ab['overdue_days'] ? '<span style="color:#0f0f0f;font-weight:700;">' . h((string) $ab['overdue_days']) . ' days</span>' : '—' ?></td>
                                            <td class="all-borrows-actions">
                                                <?php if ($ab['status'] === 'approved'): ?>
                                                    <form method="POST" action="../handlers/return_book.php">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="borrow_id" value="<?= h((string) $ab['borrow_id']) ?>">
                                                        <button type="submit" class="btn-primary" style="font-size:13px;padding:6px 14px;">
                                                            <i class="fa-solid fa-rotate-left"></i> Mark Returned
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    —
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

            </div><!-- /page-borrow_requests -->

        </div><!-- /main-content -->
    </div><!-- /whole-dashboard -->

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <!-- JS is also in assets/ — go up one level -->
    <script src="../assets/js/app.js"></script>
</body>
</html>