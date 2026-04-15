<?php
session_start();

include '../config/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/register.php');
    exit();
}

$fullName = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($fullName === '' || $email === '' || $password === '' || $confirmPassword === '') {
    header('Location: ../pages/register.php?error=missing');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../pages/register.php?error=email');
    exit();
}

if ($password !== $confirmPassword) {
    header('Location: ../pages/register.php?error=match');
    exit();
}

if (strlen($password) < 8) {
    header('Location: ../pages/register.php?error=password');
    exit();
}

$checkSql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param('s', $email);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    header('Location: ../pages/register.php?error=exists');
    exit();
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$insertSql = "INSERT INTO users (role_id, full_name, email, password_hash, is_active) VALUES (2, ?, ?, ?, 1)";
$insertStmt = $conn->prepare($insertSql);
$insertStmt->bind_param('sss', $fullName, $email, $hashedPassword);

if ($insertStmt->execute()) {
    header('Location: ../index.php?registered=1');
    exit();
}

header('Location: ../pages/register.php?error=server');
exit();






