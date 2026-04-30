<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$error   = get_flash('flash_error');
$success = get_flash('flash_success');
$email   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim((string) ($_POST['email']    ?? ''));
    $password = (string)       ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Please enter both your email and password.';
    } else {
        try {
            $stmt = db()->prepare(
                'SELECT user_id, full_name, email, password_hash, role, is_active
                 FROM   users
                 WHERE  email = :email
                 LIMIT  1'
            );
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && (int) $user['is_active'] === 1 && password_verify($password, $user['password_hash'])) {

                session_regenerate_id(true);

                $_SESSION['user'] = [
                    'id'    => (int) $user['user_id'],
                    'name'  => $user['full_name'],
                    'email' => $user['email'],
                    'role'  => $user['role'],
                ];

                // Silently upgrade any legacy hash to bcrypt cost-12
                if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
                    db()->prepare('UPDATE users SET password_hash = :h WHERE user_id = :id')
                        ->execute([
                            ':h'  => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
                            ':id' => $user['user_id'],
                        ]);
                }

                audit('user.login', (int) $user['user_id'], 'users');
                redirect('dashboard.php');
            }

            $error = 'Invalid email or password.';

        } catch (PDOException $e) {
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
    <title>Libraread — Sign In</title>
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
                <p class="brand-tagline">Your library, organized.</p>
                <div class="brand-features">
                    <div class="feature-item"><span class="feature-dot"></span> Track borrowed books</div>
                    <div class="feature-item"><span class="feature-dot"></span> Manage your catalogue</div>
                    <div class="feature-item"><span class="feature-dot"></span> Monitor overdue records</div>
                </div>
            </div>
        </div>

        <!-- Form panel -->
        <div class="auth-form-side">
            <div class="auth-box">
                <h2 class="auth-heading">Welcome back</h2>
                <p class="auth-sub">Sign in to your Libraread account</p>

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

                <form method="post" action="login.php" novalidate>
                    <div class="form-field">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email"
                               value="<?= h($email) ?>"
                               placeholder="you@example.com"
                               autocomplete="email" required>
                    </div>

                    <div class="form-field">
                        <div class="field-label-row">
                            <label for="password">Password</label>
                            <a href="ForgotPassword.php" class="field-link">Forgot password?</a>
                        </div>
                        <div class="password-wrap">
                            <input type="password" id="password" name="password"
                                   placeholder="Enter your password"
                                   autocomplete="current-password" required>
                            <button type="button" class="toggle-pw" onclick="togglePassword(this)" tabindex="-1" aria-label="Toggle password">
                                <svg class="eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="eye-hide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-auth">Sign In</button>
                </form>

                <p class="auth-footer-link">
                    Don't have an account? <a href="register.php">Create one</a>
                </p>
            </div>
        </div>

    </div>

    <script>
    function togglePassword(btn) {
        const wrap  = btn.closest('.password-wrap');
        const input = wrap.querySelector('input');
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        btn.querySelector('.eye-show').style.display = isHidden ? 'none' : '';
        btn.querySelector('.eye-hide').style.display = isHidden ? ''     : 'none';
    }
    </script>
</body>
</html>