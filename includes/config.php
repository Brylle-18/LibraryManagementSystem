<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'library_db';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = connect_database();
    bootstrap_app_schema($pdo);

    return $pdo;
}

function connect_database(): PDO
{
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    try {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $exception) {
        $rootDsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT);
        $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
        $rootPdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci', DB_NAME));

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    }
}

function bootstrap_app_schema(PDO $pdo): void
{
    static $bootstrapped = false;

    if ($bootstrapped) {
        return;
    }

    ensure_students_table($pdo);
    ensure_books_table($pdo);
    ensure_borrow_records_table($pdo);
    ensure_users_table($pdo);
    seed_default_users($pdo);

    $bootstrapped = true;
}

function ensure_students_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS students (
            student_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            first_name VARCHAR(50) NOT NULL,
            last_name VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            enrollment_date DATE NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
}

function ensure_books_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS books (
            book_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            author VARCHAR(100) NOT NULL,
            category VARCHAR(50) NOT NULL,
            available_copies INT(11) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
}

function ensure_borrow_records_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS borrow_records (
            borrow_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            student_id INT(11) NOT NULL,
            book_id INT(11) NOT NULL,
            borrow_date DATE NOT NULL,
            return_date DATE DEFAULT NULL,
            INDEX fk_borrow_student (student_id),
            INDEX fk_borrow_book (book_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
}

function ensure_users_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            user_id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(30) NOT NULL DEFAULT 'librarian',
            reset_token VARCHAR(64) DEFAULT NULL,
            reset_expires_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP()
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );

    ensure_column($pdo, 'users', 'full_name', "ALTER TABLE users ADD COLUMN full_name VARCHAR(100) NOT NULL DEFAULT '' AFTER user_id");
    ensure_column($pdo, 'users', 'email', "ALTER TABLE users ADD COLUMN email VARCHAR(100) NOT NULL DEFAULT '' AFTER full_name");
    ensure_column($pdo, 'users', 'password_hash', "ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NOT NULL DEFAULT '' AFTER email");
    ensure_column($pdo, 'users', 'role', "ALTER TABLE users ADD COLUMN role VARCHAR(30) NOT NULL DEFAULT 'librarian' AFTER password_hash");
    ensure_column($pdo, 'users', 'reset_token', "ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL AFTER role");
    ensure_column($pdo, 'users', 'reset_expires_at', "ALTER TABLE users ADD COLUMN reset_expires_at DATETIME DEFAULT NULL AFTER reset_token");
    ensure_column($pdo, 'users', 'created_at', "ALTER TABLE users ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP() AFTER reset_expires_at");

    if (!index_exists($pdo, 'users', 'email')) {
        $pdo->exec('ALTER TABLE users ADD UNIQUE KEY email (email)');
    }

    if (!index_exists($pdo, 'users', 'reset_token')) {
        $pdo->exec('ALTER TABLE users ADD UNIQUE KEY reset_token (reset_token)');
    }

    if (column_exists($pdo, 'users', 'role_id') && table_exists($pdo, 'roles') && column_exists($pdo, 'roles', 'role_name')) {
        $pdo->exec(
            "UPDATE users u
             LEFT JOIN roles r ON r.role_id = u.role_id
             SET u.role = CASE
                 WHEN r.role_name = 'admin' THEN 'admin'
                 WHEN u.role IS NULL OR u.role = '' THEN 'librarian'
                 ELSE u.role
             END"
        );
    }

    if (column_exists($pdo, 'users', 'is_active')) {
        $pdo->exec("UPDATE users SET role = 'librarian' WHERE role IS NULL OR role = ''");
    }
}

function ensure_column(PDO $pdo, string $table, string $column, string $sql): void
{
    if (!column_exists($pdo, $table, $column)) {
        $pdo->exec($sql);
    }
}

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table'
    );
    $stmt->execute([
        ':schema' => DB_NAME,
        ':table' => $table,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute([
        ':schema' => DB_NAME,
        ':table' => $table,
        ':column' => $column,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function index_exists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND INDEX_NAME = :index_name'
    );
    $stmt->execute([
        ':schema' => DB_NAME,
        ':table' => $table,
        ':index_name' => $index,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function seed_default_users(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    if ($count > 0) {
        return;
    }

    $passwordHash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, password_hash, role, created_at)
         VALUES (:full_name, :email, :password_hash, :role, NOW())"
    );

    $stmt->execute([
        ':full_name' => 'Admin User',
        ':email' => 'admin@libraread.local',
        ':password_hash' => $passwordHash,
        ':role' => 'admin',
    ]);

    $stmt->execute([
        ':full_name' => 'Library Staff',
        ':email' => 'staff@libraread.local',
        ':password_hash' => $passwordHash,
        ':role' => 'librarian',
    ]);
}

function create_user(PDO $pdo, string $fullName, string $email, string $password, string $role = 'librarian'): void
{
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    if (column_exists($pdo, 'users', 'role_id') && column_exists($pdo, 'users', 'is_active')) {
        $stmt = $pdo->prepare(
            "INSERT INTO users (full_name, email, password_hash, role, role_id, is_active, created_at)
             VALUES (:full_name, :email, :password_hash, :role, :role_id, 1, NOW())"
        );
        $stmt->execute([
            ':full_name' => $fullName,
            ':email' => $email,
            ':password_hash' => $passwordHash,
            ':role' => $role,
            ':role_id' => $role === 'admin' ? 1 : 2,
        ]);

        return;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO users (full_name, email, password_hash, role, created_at)
         VALUES (:full_name, :email, :password_hash, :role, NOW())"
    );
    $stmt->execute([
        ':full_name' => $fullName,
        ':email' => $email,
        ':password_hash' => $passwordHash,
        ':role' => $role,
    ]);
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please sign in to continue.';
        redirect('login.php');
    }
}

function set_flash(string $key, string $message): void
{
    $_SESSION[$key] = $message;
}

function get_flash(string $key): ?string
{
    if (!isset($_SESSION[$key])) {
        return null;
    }

    $message = $_SESSION[$key];
    unset($_SESSION[$key]);

    return is_string($message) ? $message : null;
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
