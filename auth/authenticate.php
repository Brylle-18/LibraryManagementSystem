<?php
session_start();
include '../config/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../index.php');
  exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']) ? 1 : 0;

if ($email === '' || $password === '') {
  header('Location: ../index.php?error=missing');
  exit();
}

$sql = "SELECT user_id, full_name, email, password_hash, role_id, is_active FROM users WHERE email = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
  header('Location: ../index.php?error=invalid');
  exit();
}

$user = $result->fetch_assoc();

if ((int)$user['is_active'] !== 1 || !password_verify($password, $user['password_hash'])) {
  header('Location: ../index.php?error=invalid');
  exit();
}

$_SESSION['user_id'] = (int)$user['user_id'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['role_id'] = (int)$user['role_id'];

$updateSql = "UPDATE users SET last_login_at = NOW() WHERE user_id = ?";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->bind_param('i', $_SESSION['user_id']);
$updateStmt->execute();

if ($remember === 1) {
  $token = bin2hex(random_bytes(32));
  $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
  $insertSessionSql = "INSERT INTO user_sessions (user_id, session_token, remember_me, ip_address, user_agent, expires_at) VALUES (?, ?, 1, ?, ?, ?)";
  $insertSessionStmt = $conn->prepare($insertSessionSql);
  $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
  $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
  $insertSessionStmt->bind_param('issss', $_SESSION['user_id'], $token, $ipAddress, $userAgent, $expiresAt);
  $insertSessionStmt->execute();

  setcookie('remember_token', $token, [
    'expires' => strtotime($expiresAt),
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
}

header('Location: ../pages/dashboard.php');
exit();