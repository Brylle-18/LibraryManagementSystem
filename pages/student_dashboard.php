<?php
// Student Dashboard
// Protected page: Only accessible by users with role_id = 2 (student)
// Displays student's personal borrowing statistics and borrow history

include '../auth/verify.php';
require_roles([2]);  // Enforce student-only access
include '../config/db_connection.php';

$currentUserId = (int)$_SESSION['user_id'];

// Fetch current student's user information (name, role)
$userSql = "SELECT u.full_name, r.role_name FROM users u LEFT JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = ? LIMIT 1";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param('i', $currentUserId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

$borrowedCount = 0;
$activeBorrowCount = 0;
$overdueCount = 0;
$borrowHistoryRows = [];

// Get borrowing statistics: total borrows and active (not returned) borrows
// Joins with students table via student_id foreign key relationship with users table
$borrowSql = "SELECT COUNT(*) AS total_borrowed, SUM(CASE WHEN br.return_date IS NULL THEN 1 ELSE 0 END) AS active_borrows FROM borrow_records br JOIN users u ON u.student_id = br.student_id WHERE u.user_id = ?";
$borrowStmt = $conn->prepare($borrowSql);
$borrowStmt->bind_param('i', $currentUserId);
$borrowStmt->execute();
$borrowData = $borrowStmt->get_result()->fetch_assoc();

if ($borrowData) {
    $borrowedCount = (int)($borrowData['total_borrowed'] ?? 0);
    $activeBorrowCount = (int)($borrowData['active_borrows'] ?? 0);
}

// Fetch complete borrow history for this student with computed due date and status
// Due date is calculated as borrow_date + 14 days (2-week loan period)
// Status logic: 'Returned' if return_date is set, 'Overdue' if borrow_date + 14 days < today, otherwise 'Borrowed'
$historySql = "SELECT b.title, b.author, br.borrow_date, DATE_ADD(br.borrow_date, INTERVAL 14 DAY) AS due_date, br.return_date, CASE WHEN br.return_date IS NOT NULL THEN 'Returned' WHEN CURDATE() > DATE_ADD(br.borrow_date, INTERVAL 14 DAY) THEN 'Overdue' ELSE 'Borrowed' END AS borrow_status FROM borrow_records br JOIN users u ON u.student_id = br.student_id JOIN books b ON b.book_id = br.book_id WHERE u.user_id = ? ORDER BY br.borrow_date DESC";
$historyStmt = $conn->prepare($historySql);
$historyStmt->bind_param('i', $currentUserId);
$historyStmt->execute();
$historyResult = $historyStmt->get_result();

while ($row = $historyResult->fetch_assoc()) {
    if ($row['borrow_status'] === 'Overdue') {
        $overdueCount++;
    }
    $borrowHistoryRows[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libraread - Student Dashboard</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="whole-dashboard">
        <div class="left-bar">
            <div class="navbar">
                <div class="brand">
                    <div class="brand-icon">L</div>
                    <h1>Libraread</h1>
                    <span class="brand-sub">STUDENT</span>
                </div>
                <div class="menu">
                    <ul>
                        <li><a href="#" class="active" data-page="dashboard">Dashboard</a></li>
                        <li><a href="#" data-page="borrows">My Borrows</a></li>
                    </ul>
                </div>
                <div class="logout">
                    <a href="../auth/logout.php">Log Out</a>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="topbar">
                <div class="topbar-user">
                    <div class="user-avatar">S</div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($userData['full_name'] ?? 'Student'); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars(ucfirst($userData['role_name'] ?? 'student')); ?></span>
                    </div>
                </div>
                <div class="topbar-right">
                    <div class="topbar-time" id="topbar-time"></div>
                </div>
            </div>

            <div class="page active" id="page-dashboard">
                <div class="dashboard-grid" style="grid-template-columns:1fr 1fr 1fr;">
                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-number"><?php echo str_pad((string)$borrowedCount, 4, '0', STR_PAD_LEFT); ?></span>
                            <span class="stat-label">Total Borrowed Books</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-number"><?php echo str_pad((string)$activeBorrowCount, 4, '0', STR_PAD_LEFT); ?></span>
                            <span class="stat-label">Currently Borrowed</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-info">
                            <span class="stat-number"><?php echo str_pad((string)$overdueCount, 4, '0', STR_PAD_LEFT); ?></span>
                            <span class="stat-label">Overdue Books</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page" id="page-borrows">
                <div class="table-wrapper">
                    <?php if (count($borrowHistoryRows) === 0): ?>
                        <div class="empty-table-state">
                            <p>No borrow records found for this student account.</p>
                            <span>Your borrow history will appear here once books are borrowed.</span>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Book</th>
                                    <th>Author</th>
                                    <th>Borrow Date</th>
                                    <th>Due Date</th>
                                    <th>Returned Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($borrowHistoryRows as $row): ?>
                                    <?php
                                        $status = $row['borrow_status'];
                                        $badgeClass = 'badge-borrowed';
                                        if ($status === 'Returned') {
                                            $badgeClass = 'badge-returned';
                                        } elseif ($status === 'Overdue') {
                                            $badgeClass = 'badge-overdue';
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['title']); ?></td>
                                        <td><?php echo htmlspecialchars($row['author']); ?></td>
                                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['borrow_date']))); ?></td>
                                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($row['due_date']))); ?></td>
                                        <td>
                                            <?php if (!empty($row['return_date'])): ?>
                                                <?php echo htmlspecialchars(date('M d, Y', strtotime($row['return_date']))); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="status-badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/app.js"></script>
</body>
</html>
