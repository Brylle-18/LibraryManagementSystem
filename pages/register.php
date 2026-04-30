<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

if (is_logged_in()) {
    redirect('userdashboard.php');
}

$error    = null;
$fullName = trim((string) ($_POST['full_name'] ?? ''));
$email    = trim((string) ($_POST['email']     ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password        = (string) ($_POST['password']         ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'Please complete all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        try {
            $pdo   = db();
            $check = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
            $check->execute([':email' => $email]);

            if ($check->fetch()) {
                $error = 'That email is already registered.';
            } else {
                $newId = create_user($pdo, $fullName, $email, $password, 'student');
                audit('user.register', $newId, 'users');
                set_flash('flash_success', 'Account created successfully. You can now sign in.');
                redirect('login.php');
            }
        } catch (PDOException $e) {
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
    <title>Libraread — Create Account</title>
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
                    <div class="feature-item"><span class="feature-dot"></span> Free student access</div>
                    <div class="feature-item"><span class="feature-dot"></span> Browse the full catalogue</div>
                    <div class="feature-item"><span class="feature-dot"></span> Track your borrowed books</div>
                </div>
            </div>
        </div>

        <!-- Form panel -->
        <div class="auth-form-side">
            <div class="auth-box">
                <h2 class="auth-heading">Create account</h2>
                <p class="auth-sub">Join Libraread as a student member</p>

                <?php if ($error !== null): ?>
                    <div class="auth-alert error">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm.75 3.5v4a.75.75 0 0 1-1.5 0v-4a.75.75 0 0 1 1.5 0zM8 12a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
                        </svg>
                        <?= h($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="register.php" novalidate>
                    <div class="form-field">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name"
                               value="<?= h($fullName) ?>"
                               placeholder="e.g. Juan dela Cruz"
                               autocomplete="name" required>
                    </div>

                    <div class="form-field">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email"
                               value="<?= h($email) ?>"
                               placeholder="you@example.com"
                               autocomplete="email" required>
                    </div>

                    <div class="form-field">
                        <label for="password">Password</label>
                        <div class="password-wrap">
                            <input type="password" id="password" name="password"
                                   placeholder="Min. 8 characters"
                                   autocomplete="new-password" required>
                            <button type="button" class="toggle-pw" onclick="togglePassword(this)" tabindex="-1" aria-label="Toggle password">
                                <svg class="eye-show" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                </svg>
                                <svg class="eye-hide" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            </button>
                        </div>
                        <div class="pw-strength" id="pw-strength"></div>
                    </div>

                    <div class="form-field">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-wrap">
                            <input type="password" id="confirm_password" name="confirm_password"
                                   placeholder="Repeat your password"
                                   autocomplete="new-password" required>
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

                    <button type="submit" class="btn-auth">Create Account</button>
                </form>

                <p class="auth-footer-link">
                    Already have an account? <a href="login.php">Sign in</a>
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

    // Live password strength indicator
    document.getElementById('password').addEventListener('input', function () {
        const val = this.value;
        const bar = document.getElementById('pw-strength');
        let score = 0;
        if (val.length >= 8)               score++;
        if (/[A-Z]/.test(val))             score++;
        if (/[0-9]/.test(val))             score++;
        if (/[^A-Za-z0-9]/.test(val))      score++;
        const labels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
        const colors = ['', '#e74c3c', '#e67e22', '#f1c40f', '#27ae60'];
        bar.textContent = val.length > 0 ? labels[score] : '';
        bar.style.color = colors[score];
    });
    </script>
</body>
</html>