

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7fc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            width: 100%;
            max-width: 450px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            font-size: 14px;
            margin-bottom: 5px;
            color: #555;
        }
        input {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        button {
            padding: 10px;
            font-size: 16px;
            color: white;
            background-color: #007bff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        .form-toggle {
            text-align: center;
            margin-top: 10px;
        }
        .form-toggle a {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }
        .form-toggle a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Create Account</h2>
    <?php if (isset($_GET['error'])): ?>
        <p style="color:#b00020; text-align:center; margin-bottom:12px;">
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
        </p>
    <?php endif; ?>
    <form method="POST" action="../handlers/save_registration.php">
        <label>Full Name:</label>
        <input type="text" name="full_name" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <label>Confirm Password:</label>
        <input type="password" name="confirm_password" required>

        <button type="submit">Register</button>
    </form>
    <div class="form-toggle">
        <p>Already have an account? <a href="../index.php">Login here</a></p>
    </div>
</div>
</body>
</html>
