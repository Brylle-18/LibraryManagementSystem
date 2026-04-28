<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

if (is_logged_in()) {
    redirect('index.php');
}

$error = get_flash('flash_error');
$success = get_flash('flash_success');
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both your email and password.';
    } else {
        try {
            $stmt = db()->prepare('SELECT user_id, full_name, email, password_hash, role FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user'] = [
                    'id' => (int) $user['user_id'],
                    'name' => $user['full_name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                ];

                redirect('index.php');
            }

            $error = 'Invalid email or password.';
        } catch (PDOException $exception) {
            $error = 'Unable to sign in right now. Please confirm the database is imported and running.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <h1>Libraread</h1>
            <p class="subtitle">Sign In</p>
<<<<<<< HEAD:login.php

            <?php if ($error !== null): ?>
                <p class="form-alert error"><?= h($error) ?></p>
            <?php endif; ?>

            <?php if ($success !== null): ?>
                <p class="form-alert success"><?= h($success) ?></p>
            <?php endif; ?>

            <form method="post" action="login.php">
=======
            
            <form id="loginForm" method="POST" action="authenticate.php">
>>>>>>> fc13e6246d6839701f37ea3c1b57bf664a82355c:Library/index.php
                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="Enter your email"
                        value="<?= h($email) ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        required
                    >
                </div>

                <button type="submit" class="btn-login">Sign In</button>
            </form>

            <div class="footer-links">
                <a href="ForgotPassword.php">Forgot Password?</a>
                <span>|</span>
                <a href="Signup.php">Sign Up</a>
            </div>
        </div>
    </div>
</body>
</html>
