<?php

declare(strict_types=1);

// ============================================================
//  Bootstrap — session, environment, core helpers
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ── Database credentials ────────────────────────────────────
// Override via environment variables in production.
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'library_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// ============================================================
//  PDO singleton — one connection per request
// ============================================================
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        DB_HOST, DB_PORT, DB_NAME
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,   // real prepared statements
        PDO::ATTR_PERSISTENT         => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ]);

    return $pdo;
}

// ============================================================
//  ACID transaction wrapper
//  Usage:
//    $result = db_transaction(function(PDO $pdo) {
//        // all queries here are atomic
//        return $someValue;
//    });
// ============================================================
function db_transaction(callable $callback): mixed
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $result = $callback($pdo);
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// ============================================================
//  Security helpers
// ============================================================

/** HTML-escape for safe output */
function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirect and exit */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** Check if a user is logged in */
function is_logged_in(): bool
{
    return isset($_SESSION['user']['id']);
}

/**
 * Require login — redirects to login.php if not authenticated.
 * Call at the top of every protected page.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('flash_error', 'Please sign in to continue.');
        redirect('/login.php');
    }
}

/**
 * Require a specific role (or array of roles).
 * Redirects to dashboard with an error if the role doesn't match.
 */
function require_role(string|array $roles): void
{
    require_login();
    $roles      = (array) $roles;
    $userRole   = $_SESSION['user']['role'] ?? '';
    if (!in_array($userRole, $roles, true)) {
        set_flash('flash_error', 'You do not have permission to access that page.');
        redirect('/dashboard.php');
    }
}

// ============================================================
//  Flash message helpers (single-use session messages)
// ============================================================
function set_flash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function get_flash(string $key): ?string
{
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

// ============================================================
//  User creation helper (always bcrypt)
// ============================================================
function create_user(
    PDO    $pdo,
    string $fullName,
    string $email,
    string $password,
    string $role = 'student'
): int {
    $stmt = $pdo->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, is_active, created_at)
         VALUES (:full_name, :email, :password_hash, :role, 1, NOW())'
    );
    $stmt->execute([
        ':full_name'     => $fullName,
        ':email'         => $email,
        ':password_hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
        ':role'          => $role,
    ]);
    return (int) $pdo->lastInsertId();
}

// ============================================================
//  Audit log helper
// ============================================================
function audit(string $action, ?int $targetId = null, ?string $targetType = null, ?array $detail = null): void
{
    try {
        $stmt = db()->prepare(
            'INSERT INTO audit_logs (user_id, action, target_type, target_id, detail, ip_address)
             VALUES (:uid, :action, :ttype, :tid, :detail, :ip)'
        );
        $stmt->execute([
            ':uid'    => $_SESSION['user']['id'] ?? null,
            ':action' => $action,
            ':ttype'  => $targetType,
            ':tid'    => $targetId,
            ':detail' => $detail !== null ? json_encode($detail) : null,
            ':ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable) {
        // Never let audit logging break the application
    }
}