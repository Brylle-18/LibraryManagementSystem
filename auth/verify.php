<?php
session_start();

if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    include '../config/db_connection.php';

    $token = $_COOKIE['remember_token'];
    $sql = "SELECT us.user_id, u.full_name, u.email, u.role_id FROM user_sessions us JOIN users u ON u.user_id = us.user_id WHERE us.session_token = ? AND us.revoked_at IS NULL AND us.expires_at > NOW() AND u.is_active = 1 LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = (int)$user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role_id'] = (int)$user['role_id'];
    }
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}
