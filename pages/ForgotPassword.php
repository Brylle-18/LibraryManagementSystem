<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Library Management System - Forgot Password</title>
	<link rel="stylesheet" href="../assets/css/forgotpass.css">
</head>
<body class="forgot-page">
	<?php
	include '../config/db_connection.php';
	$statusClass = '';
	$statusMessage = '';

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$email = trim($_POST['email'] ?? '');

		if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$statusClass = 'error';
			$statusMessage = 'Please provide a valid email address.';
		} else {
			$lookupSql = "SELECT user_id FROM users WHERE email = ? LIMIT 1";
			$lookupStmt = $conn->prepare($lookupSql);
			$lookupStmt->bind_param('s', $email);
			$lookupStmt->execute();
			$lookupResult = $lookupStmt->get_result();

			if ($lookupResult->num_rows === 1) {
				$user = $lookupResult->fetch_assoc();
				$token = bin2hex(random_bytes(32));
				$expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

				$insertSql = "INSERT INTO password_resets (user_id, reset_token, expires_at) VALUES (?, ?, ?)";
				$insertStmt = $conn->prepare($insertSql);
				$insertStmt->bind_param('iss', $user['user_id'], $token, $expiresAt);
				$insertStmt->execute();
			}

			$statusClass = 'success';
			$statusMessage = 'If this email is registered, a reset link has been prepared.';
		}
	}
	?>
	<div class="forgot-container">
		<div class="forgot-box">
			<h1>Libraread</h1>
			<p class="subtitle">Forgot Password</p>
			<p class="helper-text">Enter your registered email address and we will send a reset link.</p>

			<form id="forgotPasswordForm" method="POST" action="ForgotPassword.php">
				<div class="form-group">
					<label for="email">Email</label>
					<input
						type="email"
						id="email"
						name="email"
						placeholder="Enter your email"
						required
					>
				</div>

				<button type="submit" class="btn-submit">Send Reset Link</button>
			</form>

			<p class="status-msg <?php echo $statusClass !== '' ? $statusClass . ' show' : ''; ?>" id="statusMsg" aria-live="polite"><?php echo htmlspecialchars($statusMessage); ?></p>

			<div class="footer-links">
				<a href="../index.php">Back to Sign In</a>
				<span>|</span>
				<a href="register.php">Create Account</a>
			</div>
		</div>
	</div>
</body>
</html>
