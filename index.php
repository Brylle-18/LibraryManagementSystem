<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/dashboard_queries.php';

require_login();

$activePage = $_GET['page'] ?? 'dashboard';
$activeTab = $_GET['catalog_tab'] ?? 'borrowed';
$catalogSearch = trim((string) ($_GET['catalog_search'] ?? ''));
$booksSearch = trim((string) ($_GET['books_search'] ?? ''));
$usersSearch = trim((string) ($_GET['users_search'] ?? ''));

$dashboardError = null;
$stats = ['users' => 0, 'books' => 0, 'borrowed' => 0, 'returned' => 0];
$admins = [];
$overdueBorrowers = [];
$borrowedBooks = [];
$books = [];
$students = [];

try {
    $pdo = db();
    $stats = fetch_dashboard_stats($pdo);
    $admins = fetch_admins($pdo);
    $overdueBorrowers = fetch_overdue_borrowers($pdo);
    $borrowedBooks = fetch_borrowed_books($pdo, $catalogSearch);
    $books = fetch_books($pdo, $booksSearch);
    $students = fetch_students($pdo, $usersSearch);
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
    <title>Libraread</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="dashboard.css">
</head>
<body data-active-page="<?= h($activePage) ?>" data-active-tab="<?= h($activeTab) ?>">
    <div class="whole-dashboard">
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
                        <li><a href="?page=dashboard" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>" data-page="dashboard"><i class="fa-solid fa-grid-2"></i> Dashboard</a></li>
                        <li><a href="?page=catalog&catalog_tab=borrowed" class="<?= $activePage === 'catalog' ? 'active' : '' ?>" data-page="catalog"><i class="fa-solid fa-book-bookmark"></i> Catalog</a></li>
                        <li><a href="?page=books" class="<?= $activePage === 'books' ? 'active' : '' ?>" data-page="books"><i class="fa-solid fa-books"></i> Books</a></li>
                        <li><a href="?page=users" class="<?= $activePage === 'users' ? 'active' : '' ?>" data-page="users"><i class="fa-solid fa-users"></i> Users</a></li>
                    </ul>
                </div>
                <div class="logout">
                    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
                </div>
            </div>
        </div>

        <div class="main-content">
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
                <div class="empty-table-state" style="margin-bottom: 20px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <p><?= h($dashboardError) ?></p>
                </div>
            <?php endif; ?>

            <div class="page <?= $activePage === 'dashboard' ? 'active' : '' ?>" id="page-dashboard">
                <div class="dashboard-grid">
                    <div class="dash-chart-area">
                        <canvas id="borrowChart" width="320" height="320"></canvas>
                        <div class="chart-legend">
                            <span class="legend-dot dark"></span> Total Borrowed Books
                            <span class="legend-dot mid"></span> Total Returned Books
                        </div>
                    </div>

                    <div class="dash-stats">
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa-solid fa-user"></i></div>
                            <div class="stat-divider"></div>
                            <div class="stat-info">
                                <span class="stat-number" id="stat-users"><?= str_pad((string) $stats['users'], 4, '0', STR_PAD_LEFT) ?></span>
                                <span class="stat-label">Total User Base</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa-solid fa-book-open-reader"></i></div>
                            <div class="stat-divider"></div>
                            <div class="stat-info">
                                <span class="stat-number" id="stat-books"><?= str_pad((string) $stats['books'], 4, '0', STR_PAD_LEFT) ?></span>
                                <span class="stat-label">Total Book Count</span>
                            </div>
                        </div>
                    </div>

                    <div class="dash-panel">
                        <h3 class="panel-title">Overdue Borrowers</h3>
                        <div class="panel-list" id="overdue-list">
                            <?php if ($overdueBorrowers === []): ?>
                                <div class="empty-state"><i class="fa-solid fa-circle-check"></i><p>No overdue borrowers</p></div>
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

                    <div class="dash-admins">
                        <h3 class="panel-title">Libraread Admins</h3>
                        <div class="panel-list" id="admin-list">
                            <?php if ($admins === []): ?>
                                <div class="empty-state"><i class="fa-solid fa-user-shield"></i><p>No admins found</p></div>
                            <?php else: ?>
                                <?php foreach ($admins as $admin): ?>
                                    <div class="person-row">
                                        <div class="person-icon"><i class="fa-solid fa-user-shield"></i></div>
                                        <div class="person-details">
                                            <div class="person-name"><?= h($admin['full_name']) ?></div>
                                            <div class="person-sub"><?= h($admin['email']) ?></div>
                                            <span><span class="status-dot"></span><small><?= h(ucfirst($admin['role'])) ?></small></span>
                                        </div>
                                        <i class="fa-solid fa-user-gear person-action"></i>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page <?= $activePage === 'catalog' ? 'active' : '' ?>" id="page-catalog">
                <div class="page-header">
                    <div class="tab-group">
                        <a class="tab <?= $activeTab === 'borrowed' ? 'active' : '' ?>" href="?page=catalog&catalog_tab=borrowed&catalog_search=<?= urlencode($catalogSearch) ?>" data-tab="borrowed">Borrowed Books</a>
                        <a class="tab <?= $activeTab === 'overdue' ? 'active' : '' ?>" href="?page=catalog&catalog_tab=overdue&catalog_search=<?= urlencode($catalogSearch) ?>" data-tab="overdue">Overdue Borrowers</a>
                    </div>
                    <form class="search-bar" method="get" action="index.php">
                        <input type="hidden" name="page" value="catalog">
                        <input type="hidden" name="catalog_tab" value="<?= h($activeTab) ?>">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search by record, student, or book ID" id="catalog-search" name="catalog_search" value="<?= h($catalogSearch) ?>">
                    </form>
                </div>

                <div class="tab-content <?= $activeTab === 'borrowed' ? 'active' : '' ?>" id="tab-borrowed">
                    <div class="table-wrapper" id="borrowed-table-wrapper">
                        <?php if ($borrowedBooks === []): ?>
                            <div class="empty-table-state">
                                <i class="fa-solid fa-book-open"></i>
                                <p>No borrowed books records found.</p>
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

            <div class="page <?= $activePage === 'books' ? 'active' : '' ?>" id="page-books">
                <div class="page-header">
                    <form class="search-bar" method="get" action="index.php">
                        <input type="hidden" name="page" value="books">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search books..." id="books-search" name="books_search" value="<?= h($booksSearch) ?>">
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
                                        <td><?= h($book['category']) ?></td>
                                        <td><?= h((string) $book['available_copies']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="page <?= $activePage === 'users' ? 'active' : '' ?>" id="page-users">
                <div class="page-header">
                    <form class="search-bar" method="get" action="index.php">
                        <input type="hidden" name="page" value="users">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search users..." id="users-search" name="users_search" value="<?= h($usersSearch) ?>">
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
                                    <th>Student ID</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Email</th>
                                    <th>Enrollment Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?= h((string) $student['student_id']) ?></td>
                                        <td><?= h($student['first_name']) ?></td>
                                        <td><?= h($student['last_name']) ?></td>
                                        <td><?= h($student['email']) ?></td>
                                        <td><?= h($student['enrollment_date']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.borrowChartData = {
            borrowed: <?= (int) $stats['borrowed'] ?>,
            returned: <?= (int) $stats['returned'] ?>
        };
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script src="app.js"></script>
</body>
</html>
