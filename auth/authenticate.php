<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../pages/login.php');
}

$email    = trim((string) ($_POST['email']    ?? ''));
$password = (string)       ($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    set_flash('flash_error', 'Please enter both your email and password.');
    redirect('../pages/login.php');
}

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

        // Role-based redirect
        $role = $_SESSION['user']['role'];
        if ($role === 'student') {
            redirect('../pages/userdashboard.php');
        } else {
            redirect('../pages/dashboard.php');
        }
    }

    set_flash('flash_error', 'Invalid email or password.');
    redirect('../pages/login.php');

} catch (PDOException $e) {
    set_flash('flash_error', 'Unable to sign in right now. Please confirm the database is imported and running.');
    redirect('../pages/login.php');
}