

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: linear-gradient(135deg, #f5e6d3 0%, #e8d4bb 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            width: 100%;
            max-width: 450px;
            background-color: white;
            box-shadow: 0 10px 30px rgba(139, 111, 71, 0.2);
            border-radius: 8px;
            padding: 40px;
            border: 2px solid #8b6f47;
        }
        h2 {
            text-align: center;
            color: #6b4423;
            margin-bottom: 30px;
            font-size: 24px;
            font-weight: 700;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        label {
            font-size: 14px;
            color: #6b4423;
            font-weight: 500;
        }
        input {
            padding: 14px 16px;
            border: 1px solid #d4a574;
            border-radius: 4px;
            font-size: 15px;
            color: #333333;
            background-color: #ffffff;
            transition: border-color 0.2s ease;
        }
        input:focus {
            outline: none;
            border-color: #8b6f47;
            box-shadow: 0 0 5px rgba(139, 111, 71, 0.3);
        }
        button {
            padding: 14px;
            font-size: 15px;
            color: white;
            background-color: #8b6f47;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 10px;
        }
        button:hover {
            background-color: #6b4423;
            box-shadow: 0 4px 12px rgba(107, 68, 35, 0.3);
        }
        .form-toggle {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #6b4423;
        }
        .form-toggle a {
            color: #6b4423;
            text-decoration: none;
            font-weight: 500;
        }
        .form-toggle a:hover {
            text-decoration: underline;
            color: #8b6f47;
        }
        .error-message {
            background-color: #fff4f4;
            border: 1px solid #ffc6c6;
            color: #8a2222;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Create Account</h2>
    <?php if (isset($_GET['error'])): ?>
        <div class="error-message">
            <?php
            $error = $_GET['error'];
            if ($error === 'missing') {
                echo 'Please complete all fields.';
            } elseif ($error === 'email') {
                echo 'Please use a valid email address.';
            } elseif ($error === 'match') {
                echo 'Passwords do not match.';
            } elseif ($error === 'password') {
                echo 'Password must be at least 8 characters.';
            } elseif ($error === 'exists') {
                echo 'An account with this email already exists.';
            } else {
                echo 'Unable to register right now. Please try again.';
            }
            ?>
        </div>
    <?php endif; ?>
    <form method="POST" action="../handlers/save_registration.php">
        <div>
            <label for="full_name">Full Name:</label>
            <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" required>
        </div>

        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" required>
        </div>

        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Minimum 8 characters" required>
        </div>

        <div>
            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
        </div>

        <button type="submit">Register</button>
    </form>
    <div class="form-toggle">
        <p>Already have an account? <a href="../index.php">Login here</a></p>
    </div>
</div>
</body>
</html>
