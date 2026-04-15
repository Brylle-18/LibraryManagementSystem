<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System - Login</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <h1>Libraread</h1>
            <p class="subtitle">Sign In</p>

            <?php if (isset($_GET['error'])): ?>
                <p style="color:#b00020; text-align:center; margin-bottom:12px;">
                    <?php
                    if ($_GET['error'] === 'missing') {
                        echo 'Please enter both email and password.';
                    } else {
                        echo 'Invalid email or password.';
                    }
                    ?>
                </p>
            <?php endif; ?>

            <?php if (isset($_GET['registered'])): ?>
                <p style="color:#1b5e20; text-align:center; margin-bottom:12px;">Registration successful. Please sign in.</p>
            <?php endif; ?>
            
            <form id="loginForm" method="POST" action="auth/authenticate.php">
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

                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password" 
                        required
                    >
                </div>

                <div class="form-group checkbox">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>

                <button type="submit" class="btn-login">Sign In</button>
            </form>

            <div class="footer-links">
                <a href="pages/ForgotPassword.php">Forgot Password?</a>
                <span>|</span>
                <a href="pages/register.php">Sign Up</a>
            </div>
        </div>
    </div>
</body>
</html>
