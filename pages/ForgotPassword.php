<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error   = null;
$success = null;
$token   = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$email   = trim((string) ($_POST['email'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['request_reset'])) {
            if ($email === '') {
                $error = 'Please provide your email address.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please enter a valid email address.';
            } else {
                $stmt = db()->prepare(
                    'SELECT user_id FROM users WHERE email = :email AND is_active = 1 LIMIT 1'
                );
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                if ($user) {
                    $rawToken  = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    db()->prepare(
                        'UPDATE users SET reset_token = :token, reset_expires_at = :expires WHERE user_id = :id'
                    )->execute([
                        ':token'   => $rawToken,
                        ':expires' => $expiresAt,
                        ':id'      => $user['user_id'],
                    ]);
                }
                // Always show same message — prevents email enumeration
                $success = 'If that address is registered, use this reset link (local dev): '
                    . '<a href="ForgotPassword.php?token=' . urlencode($rawToken ?? '') . '">Click here to reset</a>';
            }
        }

        if (isset($_POST['reset_password'])) {
            $newPassword     = (string) ($_POST['password']         ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

            if ($token === '') {
                $error = 'The reset token is missing.';
            } elseif ($newPassword === '' || $confirmPassword === '') {
                $error = 'Please fill in both password fields.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'The new passwords do not match.';
            } elseif (strlen($newPassword) < 8) {
                $error = 'Password must be at least 8 characters.';
            } else {
                $stmt = db()->prepare(
                    'SELECT user_id FROM users
                     WHERE reset_token = :token
                       AND reset_expires_at IS NOT NULL
                       AND reset_expires_at >= NOW()
                       AND is_active = 1
                     LIMIT 1'
                );
                $stmt->execute([':token' => $token]);
                $user = $stmt->fetch();

                if (!$user) {
                    $error = 'This reset link is invalid or has expired.';
                } else {
                    db()->prepare(
                        'UPDATE users
                         SET password_hash      = :hash,
                             reset_token        = NULL,
                             reset_expires_at   = NULL
                         WHERE user_id = :id'
                    )->execute([
                        ':hash' => password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]),
                        ':id'   => $user['user_id'],
                    ]);

                    set_flash('flash_success', 'Password updated. You can now sign in.');
                    redirect('login.php');
                }
            }
        }
    } catch (PDOException $e) {
        $error = 'Unable to complete reset. Please check the database is running.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libraread — Reset Password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-page">
<div class="auth-split">
    <div class="auth-brand">
        <div class="auth-brand-inner">
            <div class="brand-logo">
                <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
                    <rect width="36" height="36" rx="8" fill="white"/>
                    <path d="M9 8h12a7 7 0 0 1 0 14H9V8z" fill="#111"/>
                    <path d="M9 22h13a5 5 0 0 1 0 10H9V22z" fill="#555"/>
                </svg>
            </div>
            <h1 class="brand-name">Libraread</h1>
            <p class="brand-tagline">Reset your password securely.</p>
            <div class="brand-features">
                <div class="feature-item"><span class="feature-dot"></span> Token-based reset flow</div>
                <div class="feature-item"><span class="feature-dot"></span> One-hour expiry window</div>
                <div class="feature-item"><span class="feature-dot"></span> bcrypt-protected passwords</div>
            </div>
        </div>
    </div>

    <div class="auth-form-side">
        <div class="auth-box">
            <?php if ($token === ''): ?>
                <h2 class="auth-heading">Forgot password?</h2>
                <p class="auth-sub">Enter your email to generate a reset link.</p>
            <?php else: ?>
                <h2 class="auth-heading">Set new password</h2>
                <p class="auth-sub">Choose a strong password for your account.</p>
            <?php endif; ?>

            <?php if ($error !== null): ?>
                <div class="auth-alert error"><?= h($error) ?></div>
            <?php endif; ?>
            <?php if ($success !== null): ?>
                <div class="auth-alert success"><?= $success ?></div>
            <?php endif; ?>

            <?php if ($token === ''): ?>
                <form method="post" action="ForgotPassword.php" novalidate>
                    <div class="form-field">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email"
                               value="<?= h($email) ?>" placeholder="you@example.com"
                               autocomplete="email" required>
                    </div>
                    <input type="hidden" name="request_reset" value="1">
                    <button type="submit" class="btn-auth">Generate Reset Link</button>
                </form>
            <?php else: ?>
                <form method="post" action="ForgotPassword.php" novalidate>
                    <input type="hidden" name="token" value="<?= h($token) ?>">
                    <div class="form-field">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password"
                               placeholder="Min. 8 characters"
                               autocomplete="new-password" required>
                    </div>
                    <div class="form-field">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password"
                               placeholder="Repeat new password"
                               autocomplete="new-password" required>
                    </div>
                    <input type="hidden" name="reset_password" value="1">
                    <button type="submit" class="btn-auth">Update Password</button>
                </form>
            <?php endif; ?>

            <p class="auth-footer-link">
                Remember it? <a href="login.php">Sign in</a> &nbsp;&middot;&nbsp; <a href="register.php">Create account</a>
            </p>
        </div>
    </div>
</div>
</body>
</html>


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['request_reset'])) {
            if ($email === '') {
                $error = 'Please provide your email address.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Please provide a valid email address.';
            } else {
                $stmt = db()->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                if ($user) {
                    $token     = bin2hex(random_bytes(16));
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    db()->prepare(
                        'UPDATE users SET reset_token = :token, reset_expires_at = :expires WHERE user_id = :id'
                    )->execute([':token' => $token, ':expires' => $expiresAt, ':id' => $user['user_id']]);

                    $success = 'Reset link generated. Use this local link: ForgotPassword.php?token=' . $token;
                } else {
                    $error = 'No account was found for that email address.';
                }
            }
        }

        if (isset($_POST['reset_password'])) {
            $newPassword     = (string) ($_POST['password']         ?? '');
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
                    db()->prepare(
                        'UPDATE users
                         SET password_hash = :password_hash,
                             reset_token = NULL,
                             reset_expires_at = NULL
                         WHERE user_id = :id'
                    )->execute([
                        ':password_hash' => password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]),
                        ':id'            => $user['user_id'],
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
    <title>Libraread — Forgot Password</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-page">
    <div class="auth-split">

        <!-- Brand panel -->
        <div class="auth-brand">
            <div class="auth-brand-inner">
                <div class="brand-logo">
                    <svg width="36" height="36" viewBox="0 0 36 36" fill="none">
                        <rect width="36" height="36" rx="8" fill="white"/>
                        <path d="M9 8h12a7 7 0 0 1 0 14H9V8z" fill="#111"/>
                        <path d="M9 22h13a5 5 0 0 1 0 10H9V22z" fill="#555"/>
                    </svg>
                </div>
                <h1 class="brand-name">Libraread</h1>
                <p class="brand-tagline">Reset your password securely.</p>
                <div class="brand-features">
                    <div class="feature-item"><span class="feature-dot"></span> Token-based reset flow</div>
                    <div class="feature-item"><span class="feature-dot"></span> One-hour expiry window</div>
                    <div class="feature-item"><span class="feature-dot"></span> bcrypt-protected passwords</div>
                </div>
            </div>
        </div>

        <!-- Form panel -->
        <div class="auth-form-side">
            <div class="auth-box">
                <?php if ($token === ''): ?>
                    <h2 class="auth-heading">Forgot password</h2>
                    <p class="auth-sub">Enter your registered email to generate a reset link.</p>
                <?php else: ?>
                    <h2 class="auth-heading">Reset password</h2>
                    <p class="auth-sub">Create a new password for your account.</p>
                <?php endif; ?>

                <?php if ($error !== null): ?>
                    <div class="auth-alert error">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm.75 3.5v4a.75.75 0 0 1-1.5 0v-4a.75.75 0 0 1 1.5 0zM8 12a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                        </svg>
                        <?= h($error) ?>
                    </div>
                <?php endif; ?>

                <?php if ($success !== null): ?>
                    <div class="auth-alert success">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm3.28 5.03-4 4a.75.75 0 0 1-1.06 0l-1.5-1.5a.75.75 0 1 1 1.06-1.06l.97.97 3.47-3.47a.75.75 0 1 1 1.06 1.06z"/>
                        </svg>
                        <?= h($success) ?>
                    </div>
                <?php endif; ?>

                <?php if ($token === ''): ?>
                    <form method="post" action="ForgotPassword.php" novalidate>
                        <div class="form-field">
                            <label for="email">Email address</label>
                            <input type="email" id="email" name="email"
                                   value="<?= h($email) ?>"
                                   placeholder="you@example.com"
                                   autocomplete="email" required>
                        </div>
                        <input type="hidden" name="request_reset" value="1">
                        <button type="submit" class="btn-auth">Generate Reset Link</button>
                    </form>
                <?php else: ?>
                    <form method="post" action="ForgotPassword.php" novalidate>
                        <input type="hidden" name="token" value="<?= h($token) ?>">
                        <div class="form-field">
                            <label for="password">New Password</label>
                            <input type="password" id="password" name="password"
                                   placeholder="Min. 8 characters"
                                   autocomplete="new-password" required>
                        </div>
                        <div class="form-field">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                   placeholder="Repeat your password"
                                   autocomplete="new-password" required>
                        </div>
                        <input type="hidden" name="reset_password" value="1">
                        <button type="submit" class="btn-auth">Update Password</button>
                    </form>
                <?php endif; ?>

                <p class="auth-footer-link">
                    Remember it? <a href="login.php">Sign in</a> &nbsp;&middot;&nbsp; <a href="register.php">Create account</a>
                </p>
            </div>
        </div>

    </div>
</body>
</html>
</html>
