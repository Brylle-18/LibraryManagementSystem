<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Library Management System - Forgot Password</title>
	<link rel="stylesheet" href="forgotpass.css">
</head>
<body class="forgot-page">
	<div class="forgot-container">
		<div class="forgot-box">
			<h1>Libraread</h1>
			<p class="subtitle">Forgot Password</p>
			<p class="helper-text">Enter your registered email address and we will send a reset link.</p>

			<form id="forgotPasswordForm">
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

			<p class="status-msg" id="statusMsg" aria-live="polite"></p>

			<div class="footer-links">
				<a href="login.html">Back to Sign In</a>
				<span>|</span>
				<a href="Signup.html">Create Account</a>
			</div>
		</div>
	</div>

	<script>
		const form = document.getElementById('forgotPasswordForm');
		const statusMsg = document.getElementById('statusMsg');

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			const email = document.getElementById('email').value.trim();

			if (!email) {
				statusMsg.textContent = 'Please provide your email address.';
				statusMsg.className = 'status-msg error show';
				return;
			}

			statusMsg.textContent = 'Reset link request sent. Backend integration is the next step.';
			statusMsg.className = 'status-msg success show';
			form.reset();
		});
	</script>
</body>
</html>
