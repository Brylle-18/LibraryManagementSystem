<?php
session_start();

include '../config/db_connection.php';

if (isset($_COOKIE['remember_token'])) {
	$token = $_COOKIE['remember_token'];
	$revokeSql = "UPDATE user_sessions SET revoked_at = NOW() WHERE session_token = ?";
	$revokeStmt = $conn->prepare($revokeSql);
	$revokeStmt->bind_param('s', $token);
	$revokeStmt->execute();

	setcookie('remember_token', '', time() - 3600, '/');
}

$_SESSION = [];
session_destroy();
header('Location: ../index.php');
exit;