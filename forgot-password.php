<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';

$error = null;
$success = null;
$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['request_reset'])) {
            if ($email === '') {
                $error = 'Please provide your email address.';
            } else {
                $stmt = db()->prepare('SELECT user_id, email FROM users WHERE email = :email LIMIT 1');
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                if ($user) {
                    $token = bin2hex(random_bytes(16));
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    $update = db()->prepare('UPDATE users SET reset_token = :token, reset_expires_at = :expires WHERE user_id = :id');
                    $update->execute([
                        ':token' => $token,
                        ':expires' => $expiresAt,
                        ':id' => $user['user_id'],
                    ]);

                    $success = 'Reset link generated. Use this local link: forgot-password.php?token=' . $token;
                } else {
                    $error = 'No account was found for that email address.';
                }
            }
        }

        if (isset($_POST['reset_password'])) {
            $newPassword = (string) ($_POST['password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

            if ($token === '') {
                $error = 'The reset token is missing.';
            } elseif ($newPassword === '' || $confirmPassword === '') {
                $error = 'Please fill in both password fields.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'The new passwords do not match.';
            } elseif (strlen($newPassword) < 8) {
                $error = 'The new password must be at least 8 characters long.';
            } else {
                $stmt = db()->prepare(
                    'SELECT user_id FROM users
                     WHERE reset_token = :token
                       AND reset_expires_at IS NOT NULL
                       AND reset_expires_at >= NOW()
                     LIMIT 1'
                );
                $stmt->execute([':token' => $token]);
                $user = $stmt->fetch();

                if (!$user) {
                    $error = 'This reset link is invalid or has expired.';
                } else {
                    $update = db()->prepare(
                        'UPDATE users
                         SET password_hash = :password_hash,
                             reset_token = NULL,
                             reset_expires_at = NULL
                         WHERE user_id = :id'
                    );
                    $update->execute([
                        ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                        ':id' => $user['user_id'],
                    ]);

                    set_flash('flash_success', 'Your password has been updated. You can now sign in.');
                    redirect('login.php');
                }
            }
        }
    } catch (PDOException $exception) {
        $error = 'Unable to complete the reset flow right now. Please confirm the database is imported and running.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Forgot Password</title>
    <link rel="stylesheet" href="forgotpass.css">
</head>
<body class="forgot-page">
    <div class="forgot-container">
        <div class="forgot-box">
            <h1>Libraread</h1>
            <?php if ($token === ''): ?>
                <p class="subtitle">Forgot Password</p>
                <p class="helper-text">Enter your registered email address to generate a local reset link for this project.</p>
            <?php else: ?>
                <p class="subtitle">Reset Password</p>
                <p class="helper-text">Create a new password for your account.</p>
            <?php endif; ?>

            <?php if ($error !== null): ?>
                <p class="status-msg error show"><?= h($error) ?></p>
            <?php endif; ?>

            <?php if ($success !== null): ?>
                <p class="status-msg success show"><?= h($success) ?></p>
            <?php endif; ?>

            <?php if ($token === ''): ?>
                <form method="post" action="forgot-password.php">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" value="<?= h($email) ?>" required>
                    </div>

                    <input type="hidden" name="request_reset" value="1">
                    <button type="submit" class="btn-submit">Generate Reset Link</button>
                </form>
            <?php else: ?>
                <form method="post" action="forgot-password.php">
                    <input type="hidden" name="token" value="<?= h($token) ?>">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" placeholder="Enter a new password" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required>
                    </div>

                    <input type="hidden" name="reset_password" value="1">
                    <button type="submit" class="btn-submit">Update Password</button>
                </form>
            <?php endif; ?>

            <div class="footer-links">
                <a href="login.php">Back to Sign In</a>
                <span>|</span>
                <a href="signup.php">Create Account</a>
            </div>
        </div>
    </div>
</body>
</html>
