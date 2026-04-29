<?php
include '../auth/verify.php';
require_roles([1]);
include '../config/db_connection.php';

$currentUserId = (int)$_SESSION['user_id'];

function dashboard_redirect(string $page): void
{
    header('Location: dashboard.php?page=' . urlencode($page));
    exit();
}

function set_flash(string $type, string $message): void
{
    $_SESSION['dashboard_flash'] = ['type' => $type, 'message' => $message];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'book_add') {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $copies = (int)($_POST['available_copies'] ?? 0);

        if ($title === '' || $author === '' || $category === '' || $copies < 0) {
            set_flash('error', 'Please provide valid book details.');
            dashboard_redirect('books');
        }

        $insertSql = 'INSERT INTO books (title, author, category, available_copies) VALUES (?, ?, ?, ?)';
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param('sssi', $title, $author, $category, $copies);
        if ($insertStmt->execute()) {
            set_flash('success', 'Book added successfully.');
        } else {
            set_flash('error', 'Unable to add book right now.');
        }
        dashboard_redirect('books');
    }

    if ($action === 'book_update') {
        $bookId = (int)($_POST['book_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $copies = (int)($_POST['available_copies'] ?? 0);

        if ($bookId <= 0 || $title === '' || $author === '' || $category === '' || $copies < 0) {
            set_flash('error', 'Please provide valid book details for update.');
            dashboard_redirect('books');
        }

        $updateSql = 'UPDATE books SET title = ?, author = ?, category = ?, available_copies = ? WHERE book_id = ?';
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param('sssii', $title, $author, $category, $copies, $bookId);
        if ($updateStmt->execute()) {
            set_flash('success', 'Book updated successfully.');
        } else {
            set_flash('error', 'Unable to update book.');
        }
        dashboard_redirect('books');
    }

    if ($action === 'book_delete') {
        $bookId = (int)($_POST['book_id'] ?? 0);
        if ($bookId <= 0) {
            set_flash('error', 'Invalid book selected.');
            dashboard_redirect('books');
        }

        $deleteSql = 'DELETE FROM books WHERE book_id = ?';
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param('i', $bookId);
        if ($deleteStmt->execute()) {
            set_flash('success', 'Book deleted successfully.');
        } else {
            set_flash('error', 'Book cannot be deleted while it is referenced by borrow records.');
        }
        dashboard_redirect('books');
    }

    if ($action === 'user_add') {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = (int)($_POST['role_id'] ?? 2);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || !in_array($roleId, [1, 2], true)) {
            set_flash('error', 'Provide valid user details. Password must be at least 8 characters.');
            dashboard_redirect('users');
        }

        $checkSql = 'SELECT user_id FROM users WHERE email = ? LIMIT 1';
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('s', $email);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            set_flash('error', 'Email is already used by another user.');
            dashboard_redirect('users');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $insertUserSql = 'INSERT INTO users (role_id, full_name, email, password_hash, is_active) VALUES (?, ?, ?, ?, ?)';
        $insertUserStmt = $conn->prepare($insertUserSql);
        $insertUserStmt->bind_param('isssi', $roleId, $fullName, $email, $passwordHash, $isActive);

        if ($insertUserStmt->execute()) {
            set_flash('success', 'User added successfully.');
        } else {
            set_flash('error', 'Unable to add user right now.');
        }
        dashboard_redirect('users');
    }

    if ($action === 'user_update') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 2);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $newPassword = $_POST['new_password'] ?? '';

        if ($userId <= 0 || $fullName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($roleId, [1, 2], true)) {
            set_flash('error', 'Invalid user update payload.');
            dashboard_redirect('users');
        }

        if ($userId === $currentUserId && ($isActive !== 1 || $roleId !== 1)) {
            set_flash('error', 'You cannot remove your own admin access or deactivate your account.');
            dashboard_redirect('users');
        }

        $checkEmailSql = 'SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1';
        $checkEmailStmt = $conn->prepare($checkEmailSql);
        $checkEmailStmt->bind_param('si', $email, $userId);
        $checkEmailStmt->execute();
        $checkEmailStmt->store_result();
        if ($checkEmailStmt->num_rows > 0) {
            set_flash('error', 'Email is already used by another user.');
            dashboard_redirect('users');
        }

        if ($newPassword !== '') {
            if (strlen($newPassword) < 8) {
                set_flash('error', 'New password must be at least 8 characters.');
                dashboard_redirect('users');
            }
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateUserSql = 'UPDATE users SET full_name = ?, email = ?, role_id = ?, is_active = ?, password_hash = ? WHERE user_id = ?';
            $updateUserStmt = $conn->prepare($updateUserSql);
            $updateUserStmt->bind_param('ssiisi', $fullName, $email, $roleId, $isActive, $newHash, $userId);
        } else {
            $updateUserSql = 'UPDATE users SET full_name = ?, email = ?, role_id = ?, is_active = ? WHERE user_id = ?';
            $updateUserStmt = $conn->prepare($updateUserSql);
            $updateUserStmt->bind_param('ssiii', $fullName, $email, $roleId, $isActive, $userId);
        }

        if ($updateUserStmt->execute()) {
            set_flash('success', 'User updated successfully.');
        } else {
            set_flash('error', 'Unable to update user right now.');
        }
        dashboard_redirect('users');
    }

    if ($action === 'user_delete') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            set_flash('error', 'Invalid user selected.');
            dashboard_redirect('users');
        }
        if ($userId === $currentUserId) {
            set_flash('error', 'You cannot delete your current account.');
            dashboard_redirect('users');
        }

        $deleteUserSql = 'DELETE FROM users WHERE user_id = ?';
        $deleteUserStmt = $conn->prepare($deleteUserSql);
        $deleteUserStmt->bind_param('i', $userId);
        if ($deleteUserStmt->execute()) {
            set_flash('success', 'User deleted successfully.');
        } else {
            set_flash('error', 'Unable to delete user right now.');
        }
        dashboard_redirect('users');
    }

    if ($action === 'borrow_create') {
        $studentId = (int)($_POST['student_id'] ?? 0);
        $bookId = (int)($_POST['book_id'] ?? 0);

        if ($studentId <= 0 || $bookId <= 0) {
            set_flash('error', 'Please select a valid student and book.');
            dashboard_redirect('catalog');
        }

        $checkBookSql = 'SELECT available_copies FROM books WHERE book_id = ? LIMIT 1';
        $checkBookStmt = $conn->prepare($checkBookSql);
        $checkBookStmt->bind_param('i', $bookId);
        $checkBookStmt->execute();
        $bookResult = $checkBookStmt->get_result();
        if ($bookResult->num_rows === 0) {
            set_flash('error', 'Book not found.');
            dashboard_redirect('catalog');
        }
        $bookData = $bookResult->fetch_assoc();
        if ((int)$bookData['available_copies'] <= 0) {
            set_flash('error', 'This book has no available copies.');
            dashboard_redirect('catalog');
        }

        $checkStudentSql = 'SELECT student_id FROM students WHERE student_id = ? LIMIT 1';
        $checkStudentStmt = $conn->prepare($checkStudentSql);
        $checkStudentStmt->bind_param('i', $studentId);
        $checkStudentStmt->execute();
        $checkStudentStmt->store_result();
        if ($checkStudentStmt->num_rows === 0) {
            set_flash('error', 'Student not found.');
            dashboard_redirect('catalog');
        }

        $conn->begin_transaction();
        try {
            $insertBorrowSql = 'INSERT INTO borrow_records (student_id, book_id, borrow_date) VALUES (?, ?, CURDATE())';
            $insertBorrowStmt = $conn->prepare($insertBorrowSql);
            $insertBorrowStmt->bind_param('ii', $studentId, $bookId);
            if (!$insertBorrowStmt->execute()) {
                throw new Exception('Failed to create borrow record');
            }

            $newCopies = (int)$bookData['available_copies'] - 1;
            $updateCopiesSql = 'UPDATE books SET available_copies = ? WHERE book_id = ?';
            $updateCopiesStmt = $conn->prepare($updateCopiesSql);
            $updateCopiesStmt->bind_param('ii', $newCopies, $bookId);
            if (!$updateCopiesStmt->execute()) {
                throw new Exception('Failed to update available copies');
            }

            $conn->commit();
            set_flash('success', 'Book borrowed successfully.');
        } catch (Exception $e) {
            $conn->rollback();
            set_flash('error', 'Transaction failed: ' . $e->getMessage());
        }
        dashboard_redirect('catalog');
    }

    if ($action === 'borrow_return') {
        $borrowId = (int)($_POST['borrow_id'] ?? 0);

        if ($borrowId <= 0) {
            set_flash('error', 'Invalid borrow record.');
            dashboard_redirect('catalog');
        }

        $checkBorrowSql = 'SELECT book_id, return_date FROM borrow_records WHERE borrow_id = ? LIMIT 1';
        $checkBorrowStmt = $conn->prepare($checkBorrowSql);
        $checkBorrowStmt->bind_param('i', $borrowId);
        $checkBorrowStmt->execute();
        $borrowResult = $checkBorrowStmt->get_result();
        if ($borrowResult->num_rows === 0) {
            set_flash('error', 'Borrow record not found.');
            dashboard_redirect('catalog');
        }
        $borrowData = $borrowResult->fetch_assoc();
        if (!empty($borrowData['return_date'])) {
            set_flash('error', 'This book has already been returned.');
            dashboard_redirect('catalog');
        }

        $bookId = (int)$borrowData['book_id'];
        $checkBookCopiesSql = 'SELECT available_copies FROM books WHERE book_id = ? LIMIT 1';
        $checkBookCopiesStmt = $conn->prepare($checkBookCopiesSql);
        $checkBookCopiesStmt->bind_param('i', $bookId);
        $checkBookCopiesStmt->execute();
        $bookCopiesResult = $checkBookCopiesStmt->get_result();
        $bookCopiesData = $bookCopiesResult->fetch_assoc();

        $conn->begin_transaction();
        try {
            $updateReturnSql = 'UPDATE borrow_records SET return_date = CURDATE() WHERE borrow_id = ?';
            $updateReturnStmt = $conn->prepare($updateReturnSql);
            $updateReturnStmt->bind_param('i', $borrowId);
            if (!$updateReturnStmt->execute()) {
                throw new Exception('Failed to update borrow record');
            }

            $newCopies = (int)$bookCopiesData['available_copies'] + 1;
            $updateCopiesSql = 'UPDATE books SET available_copies = ? WHERE book_id = ?';
            $updateCopiesStmt = $conn->prepare($updateCopiesSql);
            $updateCopiesStmt->bind_param('ii', $newCopies, $bookId);
            if (!$updateCopiesStmt->execute()) {
                throw new Exception('Failed to update available copies');
            }

            $conn->commit();
            set_flash('success', 'Book returned successfully.');
        } catch (Exception $e) {
            $conn->rollback();
            set_flash('error', 'Return transaction failed: ' . $e->getMessage());
        }
        dashboard_redirect('catalog');
    }
}

$activePage = $_GET['page'] ?? 'dashboard';
if (!in_array($activePage, ['dashboard', 'catalog', 'books', 'users'], true)) {
    $activePage = 'dashboard';
}

$bookSearch = trim($_GET['book_search'] ?? '');
$userSearch = trim($_GET['user_search'] ?? '');

$flash = $_SESSION['dashboard_flash'] ?? null;
unset($_SESSION['dashboard_flash']);

$userSql = "SELECT u.full_name, r.role_name FROM users u LEFT JOIN roles r ON r.role_id = u.role_id WHERE u.user_id = ? LIMIT 1";
$userStmt = $conn->prepare($userSql);
$userStmt->bind_param('i', $currentUserId);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

$countUsers = 0;
$countBooks = 0;

$usersCountResult = $conn->query("SELECT COUNT(*) AS total_users FROM users");
if ($usersCountResult) {
    $countUsers = (int)$usersCountResult->fetch_assoc()['total_users'];
}

$booksCountResult = $conn->query("SELECT COUNT(*) AS total_books FROM books");
if ($booksCountResult) {
    $countBooks = (int)$booksCountResult->fetch_assoc()['total_books'];
}

$borrowStats = ['borrowed_total' => 0, 'returned_total' => 0];
$borrowStatsSql = "SELECT COUNT(*) AS borrowed_total, SUM(CASE WHEN return_date IS NOT NULL THEN 1 ELSE 0 END) AS returned_total FROM borrow_records";
$borrowStatsResult = $conn->query($borrowStatsSql);
if ($borrowStatsResult) {
    $borrowStatsData = $borrowStatsResult->fetch_assoc();
    $borrowStats['borrowed_total'] = (int)($borrowStatsData['borrowed_total'] ?? 0);
    $borrowStats['returned_total'] = (int)($borrowStatsData['returned_total'] ?? 0);
}

$overdueBorrowers = [];
$overdueSql = "SELECT s.student_id, CONCAT(s.first_name, ' ', s.last_name) AS student_name, b.title, br.borrow_date FROM borrow_records br JOIN students s ON s.student_id = br.student_id JOIN books b ON b.book_id = br.book_id WHERE br.return_date IS NULL AND br.borrow_date < DATE_SUB(CURDATE(), INTERVAL 14 DAY) ORDER BY br.borrow_date ASC LIMIT 8";
$overdueResult = $conn->query($overdueSql);
if ($overdueResult) {
    while ($row = $overdueResult->fetch_assoc()) {
        $overdueBorrowers[] = $row;
    }
}

$adminUsers = [];
$adminsSql = "SELECT full_name, email FROM users WHERE role_id = 1 AND is_active = 1 ORDER BY full_name ASC LIMIT 8";
$adminsResult = $conn->query($adminsSql);
if ($adminsResult) {
    while ($row = $adminsResult->fetch_assoc()) {
        $adminUsers[] = $row;
    }
}

$roles = [];
$rolesResult = $conn->query("SELECT role_id, role_name FROM roles ORDER BY role_name ASC");
if ($rolesResult) {
    while ($row = $rolesResult->fetch_assoc()) {
        $roles[] = $row;
    }
}

$books = [];
if ($bookSearch !== '') {
    $booksSql = "SELECT book_id, title, author, category, available_copies FROM books WHERE title LIKE CONCAT('%', ?, '%') OR author LIKE CONCAT('%', ?, '%') OR category LIKE CONCAT('%', ?, '%') ORDER BY title ASC";
    $booksStmt = $conn->prepare($booksSql);
    $booksStmt->bind_param('sss', $bookSearch, $bookSearch, $bookSearch);
    $booksStmt->execute();
    $booksResult = $booksStmt->get_result();
} else {
    $booksResult = $conn->query("SELECT book_id, title, author, category, available_copies FROM books ORDER BY title ASC");
}
if ($booksResult) {
    while ($row = $booksResult->fetch_assoc()) {
        $books[] = $row;
    }
}

$users = [];
if ($userSearch !== '') {
    $usersSql = "SELECT u.user_id, u.full_name, u.email, u.role_id, u.is_active, u.created_at, r.role_name FROM users u LEFT JOIN roles r ON r.role_id = u.role_id WHERE u.full_name LIKE CONCAT('%', ?, '%') OR u.email LIKE CONCAT('%', ?, '%') OR r.role_name LIKE CONCAT('%', ?, '%') ORDER BY u.created_at DESC";
    $usersStmt = $conn->prepare($usersSql);
    $usersStmt->bind_param('sss', $userSearch, $userSearch, $userSearch);
    $usersStmt->execute();
    $usersResult = $usersStmt->get_result();
} else {
    $usersResult = $conn->query("SELECT u.user_id, u.full_name, u.email, u.role_id, u.is_active, u.created_at, r.role_name FROM users u LEFT JOIN roles r ON r.role_id = u.role_id ORDER BY u.created_at DESC");
}
if ($usersResult) {
    while ($row = $usersResult->fetch_assoc()) {
        $users[] = $row;
    }
}

$catalogBorrowed = [];
$catalogBorrowedSql = "SELECT br.borrow_id, CONCAT(s.first_name, ' ', s.last_name) AS student_name, b.title, br.borrow_date, br.return_date FROM borrow_records br JOIN students s ON s.student_id = br.student_id JOIN books b ON b.book_id = br.book_id ORDER BY br.borrow_date DESC";
$catalogBorrowedResult = $conn->query($catalogBorrowedSql);
if ($catalogBorrowedResult) {
    while ($row = $catalogBorrowedResult->fetch_assoc()) {
        $catalogBorrowed[] = $row;
    }
}

$catalogOverdue = [];
$catalogOverdueSql = "SELECT br.borrow_id, CONCAT(s.first_name, ' ', s.last_name) AS student_name, b.title, br.borrow_date, DATE_ADD(br.borrow_date, INTERVAL 14 DAY) AS due_date FROM borrow_records br JOIN students s ON s.student_id = br.student_id JOIN books b ON b.book_id = br.book_id WHERE br.return_date IS NULL AND br.borrow_date < DATE_SUB(CURDATE(), INTERVAL 14 DAY) ORDER BY br.borrow_date ASC";
$catalogOverdueResult = $conn->query($catalogOverdueSql);
if ($catalogOverdueResult) {
    while ($row = $catalogOverdueResult->fetch_assoc()) {
        $catalogOverdue[] = $row;
    }
}

$students = [];
$studentsResult = $conn->query("SELECT student_id, CONCAT(first_name, ' ', last_name) AS student_name FROM students ORDER BY first_name ASC");
if ($studentsResult) {
    while ($row = $studentsResult->fetch_assoc()) {
        $students[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libraread</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWix+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkR4j8gYz8R+e1L6Nf2Wf2G7xR1Jf5f8QYKg==" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
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
                        <li><a href="#" class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>" data-page="dashboard"><i class="fa-solid fa-grid-2"></i> Dashboard</a></li>
                        <li><a href="#" class="<?php echo $activePage === 'catalog' ? 'active' : ''; ?>" data-page="catalog"><i class="fa-solid fa-book-bookmark"></i> Catalog</a></li>
                        <li><a href="#" class="<?php echo $activePage === 'books' ? 'active' : ''; ?>" data-page="books"><i class="fa-solid fa-books"></i> Books</a></li>
                        <li><a href="#" class="<?php echo $activePage === 'users' ? 'active' : ''; ?>" data-page="users"><i class="fa-solid fa-users"></i> Users</a></li>
                    </ul>
                </div>
                <div class="logout">
                    <a href="../auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
                </div>
            </div>
        </div>

        <div class="main-content">

            <div class="topbar">
                <div class="topbar-user">
                    <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($userData['full_name'] ?? 'User'); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars(ucfirst($userData['role_name'] ?? 'member')); ?></span>
                    </div>
                </div>
                <div class="topbar-right">
                    <div class="topbar-time" id="topbar-time"></div>
                    <button class="settings-btn"><i class="fa-solid fa-gear"></i></button>
                </div>
            </div>

            <?php if ($flash): ?>
                <div class="flash-wrap">
                    <div class="flash-message <?php echo $flash['type'] === 'success' ? 'flash-success' : 'flash-error'; ?>">
                        <?php echo htmlspecialchars($flash['message']); ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="page <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>" id="page-dashboard">
                <div class="dashboard-grid">

                    <div class="dash-chart-area">
                        <canvas id="borrowChart" width="320" height="320" data-borrowed="<?php echo $borrowStats['borrowed_total']; ?>" data-returned="<?php echo $borrowStats['returned_total']; ?>"></canvas>
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
                                <span class="stat-number" id="stat-users"><?php echo str_pad((string)$countUsers, 4, '0', STR_PAD_LEFT); ?></span>
                                <span class="stat-label">Total User Base</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon"><i class="fa-solid fa-book-open-reader"></i></div>
                            <div class="stat-divider"></div>
                            <div class="stat-info">
                                <span class="stat-number" id="stat-books"><?php echo str_pad((string)$countBooks, 4, '0', STR_PAD_LEFT); ?></span>
                                <span class="stat-label">Total Book Count</span>
                            </div>
                        </div>
                    </div>

                    <div class="dash-panel">
                        <h3 class="panel-title">Overdue Borrowers</h3>
                        <div class="panel-list" id="overdue-list">
                            <?php if (count($overdueBorrowers) === 0): ?>
                                <div class="empty-state"><i class="fa-solid fa-circle-check"></i><p>No overdue borrowers</p></div>
                            <?php else: ?>
                                <?php foreach ($overdueBorrowers as $overdue): ?>
                                    <div class="person-row">
                                        <div class="person-icon"><i class="fa-solid fa-user-clock"></i></div>
                                        <div class="person-details">
                                            <div class="person-name"><?php echo htmlspecialchars($overdue['student_name']); ?></div>
                                            <div class="person-sub">Book: <?php echo htmlspecialchars($overdue['title']); ?></div>
                                            <div class="person-sub">Borrowed: <?php echo htmlspecialchars(date('M d, Y', strtotime($overdue['borrow_date']))); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="dash-admins">
                        <h3 class="panel-title">Libraread Admins</h3>
                        <div class="panel-list" id="admin-list">
                            <?php if (count($adminUsers) === 0): ?>
                                <div class="empty-state"><i class="fa-solid fa-user-shield"></i><p>No admins found</p></div>
                            <?php else: ?>
                                <?php foreach ($adminUsers as $admin): ?>
                                    <div class="person-row">
                                        <div class="person-icon"><i class="fa-solid fa-user-shield"></i></div>
                                        <div class="person-details">
                                            <div class="person-name"><?php echo htmlspecialchars($admin['full_name']); ?></div>
                                            <div class="person-sub"><?php echo htmlspecialchars($admin['email']); ?></div>
                                            <span><span class="status-dot"></span><small>Active</small></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

            <div class="page <?php echo $activePage === 'catalog' ? 'active' : ''; ?>" id="page-catalog">
                <div class="page-header">
                    <h3 style="margin: 0; font-size: 0.95rem; font-weight: 600; color: #111;">Create Borrow Transaction</h3>
                </div>

                <form method="POST" action="dashboard.php?page=catalog" class="admin-form-grid borrow-form-grid">
                    <input type="hidden" name="action" value="borrow_create">
                    <select name="student_id" required>
                        <option value="" disabled selected>Select Student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo (int)$student['student_id']; ?>"><?php echo htmlspecialchars($student['student_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="book_id" required>
                        <option value="" disabled selected>Select Book</option>
                        <?php foreach ($books as $book): ?>
                            <option value="<?php echo (int)$book['book_id']; ?>" <?php echo (int)$book['available_copies'] === 0 ? 'disabled' : ''; ?>>
                                <?php echo htmlspecialchars($book['title']); ?> (<?php echo (int)$book['available_copies']; ?> available)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="add-btn"><i class="fa-solid fa-plus"></i> Borrow Book</button>
                </form>

                <div class="page-header" style="margin-top: 24px;">
                    <div class="tab-group">
                        <button class="tab active" data-tab="borrowed">Borrowed Books</button>
                        <button class="tab" data-tab="overdue">Overdue Borrowers</button>
                    </div>
                </div>

                <div class="tab-content active" id="tab-borrowed">
                    <div class="table-wrapper" id="borrowed-table-wrapper">
                        <?php if (count($catalogBorrowed) === 0): ?>
                            <div class="empty-table-state">
                                <i class="fa-solid fa-book-open"></i>
                                <p>No borrowed books records yet.</p>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Borrow ID</th>
                                        <th>Student</th>
                                        <th>Book</th>
                                        <th>Borrow Date</th>
                                        <th>Returned Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($catalogBorrowed as $record): ?>
                                        <?php
                                            $status = !empty($record['return_date']) ? 'Returned' : 'Borrowed';
                                            $statusClass = !empty($record['return_date']) ? 'badge-returned' : 'badge-borrowed';
                                        ?>
                                        <tr>
                                            <td><?php echo (int)$record['borrow_id']; ?></td>
                                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['title']); ?></td>
                                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($record['borrow_date']))); ?></td>
                                            <td><?php echo !empty($record['return_date']) ? htmlspecialchars(date('M d, Y', strtotime($record['return_date']))) : '-'; ?></td>
                                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                                            <td>
                                                <?php if (empty($record['return_date'])): ?>
                                                    <form method="POST" action="dashboard.php?page=catalog" style="display: inline;">
                                                        <input type="hidden" name="action" value="borrow_return">
                                                        <input type="hidden" name="borrow_id" value="<?php echo (int)$record['borrow_id']; ?>">
                                                        <button type="submit" class="mini-btn">Return</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span style="color: #888; font-size: 0.8rem;">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="tab-content" id="tab-overdue">
                    <div class="table-wrapper" id="overdue-table-wrapper">
                        <?php if (count($catalogOverdue) === 0): ?>
                            <div class="empty-table-state">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <p>No overdue borrowers yet.</p>
                            </div>
                        <?php else: ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Borrow ID</th>
                                        <th>Student</th>
                                        <th>Book</th>
                                        <th>Borrow Date</th>
                                        <th>Due Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($catalogOverdue as $record): ?>
                                        <tr>
                                            <td><?php echo (int)$record['borrow_id']; ?></td>
                                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['title']); ?></td>
                                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($record['borrow_date']))); ?></td>
                                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($record['due_date']))); ?></td>
                                            <td>
                                                <form method="POST" action="dashboard.php?page=catalog" style="display: inline;">
                                                    <input type="hidden" name="action" value="borrow_return">
                                                    <input type="hidden" name="borrow_id" value="<?php echo (int)$record['borrow_id']; ?>">
                                                    <button type="submit" class="mini-btn">Return</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="page <?php echo $activePage === 'books' ? 'active' : ''; ?>" id="page-books">
                <div class="page-header">
                    <form method="GET" action="dashboard.php" class="search-bar search-form">
                        <input type="hidden" name="page" value="books">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search books..." id="books-search" name="book_search" value="<?php echo htmlspecialchars($bookSearch); ?>">
                        <button type="submit" class="mini-btn">Search</button>
                    </form>
                </div>

                <form method="POST" action="dashboard.php?page=books" class="admin-form-grid">
                    <input type="hidden" name="action" value="book_add">
                    <input type="text" name="title" placeholder="Book title" required>
                    <input type="text" name="author" placeholder="Author" required>
                    <input type="text" name="category" placeholder="Category" required>
                    <input type="number" name="available_copies" placeholder="Copies" min="0" required>
                    <button type="submit" class="add-btn"><i class="fa-solid fa-plus"></i> Add Book</button>
                </form>

                <div class="table-wrapper" id="books-table-wrapper">
                    <?php if (count($books) === 0): ?>
                        <div class="empty-table-state">
                            <i class="fa-solid fa-books"></i>
                            <p>No books in the catalog yet.</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Copies</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($books as $book): ?>
                                    <tr>
                                        <td><?php echo (int)$book['book_id']; ?></td>
                                        <td>
                                            <input class="table-input" type="text" form="book-update-<?php echo (int)$book['book_id']; ?>" name="title" value="<?php echo htmlspecialchars($book['title']); ?>" required>
                                        </td>
                                        <td>
                                            <input class="table-input" type="text" form="book-update-<?php echo (int)$book['book_id']; ?>" name="author" value="<?php echo htmlspecialchars($book['author']); ?>" required>
                                        </td>
                                        <td>
                                            <input class="table-input" type="text" form="book-update-<?php echo (int)$book['book_id']; ?>" name="category" value="<?php echo htmlspecialchars($book['category']); ?>" required>
                                        </td>
                                        <td>
                                            <input class="table-input table-input-small" type="number" min="0" form="book-update-<?php echo (int)$book['book_id']; ?>" name="available_copies" value="<?php echo (int)$book['available_copies']; ?>" required>
                                        </td>
                                        <td>
                                            <div class="action-row">
                                                <form id="book-update-<?php echo (int)$book['book_id']; ?>" method="POST" action="dashboard.php?page=books">
                                                    <input type="hidden" name="action" value="book_update">
                                                    <input type="hidden" name="book_id" value="<?php echo (int)$book['book_id']; ?>">
                                                    <button type="submit" class="mini-btn">Save</button>
                                                </form>
                                                <form method="POST" action="dashboard.php?page=books" onsubmit="return confirm('Delete this book?');">
                                                    <input type="hidden" name="action" value="book_delete">
                                                    <input type="hidden" name="book_id" value="<?php echo (int)$book['book_id']; ?>">
                                                    <button type="submit" class="mini-btn mini-btn-danger">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="page <?php echo $activePage === 'users' ? 'active' : ''; ?>" id="page-users">
                <div class="page-header">
                    <form method="GET" action="dashboard.php" class="search-bar search-form">
                        <input type="hidden" name="page" value="users">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" placeholder="Search users..." id="users-search" name="user_search" value="<?php echo htmlspecialchars($userSearch); ?>">
                        <button type="submit" class="mini-btn">Search</button>
                    </form>
                </div>

                <form method="POST" action="dashboard.php?page=users" class="admin-form-grid users-form-grid">
                    <input type="hidden" name="action" value="user_add">
                    <input type="text" name="full_name" placeholder="Full name" required>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password (min 8)" minlength="8" required>
                    <select name="role_id" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?php echo (int)$role['role_id']; ?>"><?php echo htmlspecialchars(ucfirst($role['role_name'])); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="check-inline"><input type="checkbox" name="is_active" checked> Active</label>
                    <button type="submit" class="add-btn"><i class="fa-solid fa-plus"></i> Add User</button>
                </form>

                <div class="table-wrapper" id="users-table-wrapper">
                    <?php if (count($users) === 0): ?>
                        <div class="empty-table-state">
                            <i class="fa-solid fa-users"></i>
                            <p>No users registered yet.</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Active</th>
                                    <th>New Password</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?php echo (int)$u['user_id']; ?></td>
                                        <td>
                                            <input class="table-input" type="text" form="user-update-<?php echo (int)$u['user_id']; ?>" name="full_name" value="<?php echo htmlspecialchars($u['full_name']); ?>" required>
                                        </td>
                                        <td>
                                            <input class="table-input" type="email" form="user-update-<?php echo (int)$u['user_id']; ?>" name="email" value="<?php echo htmlspecialchars($u['email']); ?>" required>
                                        </td>
                                        <td>
                                            <select class="table-input" form="user-update-<?php echo (int)$u['user_id']; ?>" name="role_id">
                                                <?php foreach ($roles as $role): ?>
                                                    <option value="<?php echo (int)$role['role_id']; ?>" <?php echo (int)$u['role_id'] === (int)$role['role_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars(ucfirst($role['role_name'])); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <label class="check-inline compact"><input type="checkbox" form="user-update-<?php echo (int)$u['user_id']; ?>" name="is_active" <?php echo (int)$u['is_active'] === 1 ? 'checked' : ''; ?>> Yes</label>
                                        </td>
                                        <td>
                                            <input class="table-input" type="password" form="user-update-<?php echo (int)$u['user_id']; ?>" name="new_password" minlength="8" placeholder="Leave blank">
                                        </td>
                                        <td>
                                            <div class="action-row">
                                                <form id="user-update-<?php echo (int)$u['user_id']; ?>" method="POST" action="dashboard.php?page=users">
                                                    <input type="hidden" name="action" value="user_update">
                                                    <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                                                    <button type="submit" class="mini-btn">Save</button>
                                                </form>
                                                <form method="POST" action="dashboard.php?page=users" onsubmit="return confirm('Delete this user?');">
                                                    <input type="hidden" name="action" value="user_delete">
                                                    <input type="hidden" name="user_id" value="<?php echo (int)$u['user_id']; ?>">
                                                    <button type="submit" class="mini-btn mini-btn-danger" <?php echo (int)$u['user_id'] === $currentUserId ? 'disabled' : ''; ?>>Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script src="../assets/js/app.js"></script>
</body>
</html>