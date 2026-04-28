<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error = null;
$success = null;
$fullName = trim((string) ($_POST['full_name'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please complete all fields.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        try {
            $pdo = db();
            $check = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
            $check->execute([':email' => $email]);

            if ($check->fetch()) {
                $error = 'That email is already registered.';
            } else {
                create_user($pdo, $fullName, $email, $password, 'librarian');

                set_flash('flash_success', 'Account created successfully. You can now sign in.');
                redirect('login.php');
            }
        } catch (PDOException $exception) {
            $error = 'Unable to create the account right now. Please check your database setup.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Sign Up</title>
    <link rel="stylesheet" href="signup.css">
</head>
<body class="signup-page">
    <div class="signup-container">
        <div class="signup-box">
            <h1>Libraread</h1>
            <p class="subtitle">Create Account</p>

            <?php if ($error !== null): ?>
                <p class="form-alert error"><?= h($error) ?></p>
            <?php endif; ?>

            <?php if ($success !== null): ?>
                <p class="form-alert success"><?= h($success) ?></p>
            <?php endif; ?>

            <form method="post" action="signup.php">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?= h($fullName) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= h($email) ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>

                <button type="submit" class="btn-signup">Create Account</button>
            </form>

            <div class="footer-links">
                <a href="login.php">Back to Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>
