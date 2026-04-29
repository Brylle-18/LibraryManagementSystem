<?php
$servername = "localhost";
$username = "root";
$password = "";

$dbname = "library_db";

// Avoid mysqli throwing uncaught exceptions so we can show a friendly setup message.
mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($servername, $username, $password);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create the database on first run if it does not exist yet.
if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
    die("Database setup error: " . $conn->error);
}

if (!$conn->select_db($dbname)) {
    die("Cannot select database '$dbname'. Please check your MySQL configuration.");
}

$conn->set_charset("utf8mb4");

// Initialize database tables if they don't exist
initialize_database_tables($conn);

function initialize_database_tables($conn) {
    // Create roles table
    $conn->query("CREATE TABLE IF NOT EXISTS `roles` (
      `role_id` int(11) NOT NULL AUTO_INCREMENT,
      `role_name` varchar(30) NOT NULL,
      `created_at` datetime NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`role_id`),
      UNIQUE KEY `role_name` (`role_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Insert default roles if they don't exist
    $conn->query("INSERT IGNORE INTO `roles` (`role_id`, `role_name`) VALUES (1, 'admin'), (2, 'librarian'), (3, 'student')");

    // Create users table
    $conn->query("CREATE TABLE IF NOT EXISTS `users` (
      `user_id` int(11) NOT NULL AUTO_INCREMENT,
      `role_id` int(11) NOT NULL DEFAULT 2,
      `full_name` varchar(100) NOT NULL,
      `email` varchar(100) NOT NULL,
      `password_hash` varchar(255) NOT NULL,
      `is_active` tinyint(1) NOT NULL DEFAULT 1,
      `last_login_at` datetime DEFAULT NULL,
      `created_at` datetime NOT NULL DEFAULT current_timestamp(),
      `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`user_id`),
      UNIQUE KEY `email` (`email`),
      KEY `fk_users_role` (`role_id`),
      CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Create user_sessions table
    $conn->query("CREATE TABLE IF NOT EXISTS `user_sessions` (
      `session_id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `session_token` varchar(128) NOT NULL,
      `remember_me` tinyint(1) NOT NULL DEFAULT 0,
      `ip_address` varchar(45) DEFAULT NULL,
      `user_agent` varchar(255) DEFAULT NULL,
      `expires_at` datetime NOT NULL,
      `revoked_at` datetime DEFAULT NULL,
      `created_at` datetime NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`session_id`),
      UNIQUE KEY `session_token` (`session_token`),
      KEY `fk_user_sessions_user` (`user_id`),
      CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Create books table
    $conn->query("CREATE TABLE IF NOT EXISTS `books` (
      `book_id` int(11) NOT NULL AUTO_INCREMENT,
      `title` varchar(150) NOT NULL,
      `author` varchar(100) NOT NULL,
      `category` varchar(50) NOT NULL,
      `available_copies` int(11) NOT NULL DEFAULT 1,
      PRIMARY KEY (`book_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Create password_resets table
    $conn->query("CREATE TABLE IF NOT EXISTS `password_resets` (
      `reset_id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `reset_token` varchar(128) NOT NULL,
      `expires_at` datetime NOT NULL,
      `used_at` datetime DEFAULT NULL,
      `created_at` datetime NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`reset_id`),
      UNIQUE KEY `reset_token` (`reset_token`),
      KEY `fk_password_resets_user` (`user_id`),
      CONSTRAINT `fk_password_resets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // Insert default admin user if no users exist
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $adminPass = password_hash('admin123', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO `users` (`role_id`, `full_name`, `email`, `password_hash`, `is_active`) 
                     VALUES (1, 'Admin User', 'admin@libraread.local', '$adminPass', 1)");
    }
}