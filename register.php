<?php
session_start();
include 'config.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, password) VALUES ('$username', '$password')";
    if ($conn->query($sql) === TRUE) {
        $message = 'Registration successful! <a href="login.php">Login here</a>.';
    } else {
        $message = 'Error: ' . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            background: linear-gradient(120deg, #f7cac9 0%, #92a8d1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .register-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(44,62,80,0.18);
            padding: 40px 32px 32px 32px;
            width: 350px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .register-card:before {
            content: '\1F6D2'; /* Shopping cart emoji */
            font-size: 2.5rem;
            position: absolute;
            top: -38px;
            left: 50%;
            transform: translateX(-50%);
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(44,62,80,0.10);
            padding: 10px 18px;
        }
        .register-card h2 {
            margin-top: 18px;
            margin-bottom: 18px;
            color: #2c3e50;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .register-card label {
            font-weight: 500;
            color: #555;
        }
        .register-card input[type="text"],
        .register-card input[type="password"] {
            width: 100%;
            margin-bottom: 18px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
        }
        .register-card button {
            width: 100%;
            background: #3498db;
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            margin-top: 8px;
            transition: background 0.2s;
        }
        .register-card button:hover {
            background: #217dbb;
        }
        .register-card .message {
            color: #e74c3c;
            margin: 10px 0 0 0;
            font-size: 1rem;
            min-height: 22px;
        }
        .register-card .login-link {
            margin-top: 18px;
            font-size: 0.98rem;
        }
        .register-card .login-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        .register-card .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <h2>Register</h2>
        <form method="post">
            <label>Username:</label><br>
            <input type="text" name="username" required><br>
            <label>Password:</label><br>
            <input type="password" name="password" required><br>
            <button type="submit">Register</button>
        </form>
        <div class="message"><?php echo $message; ?></div>
        <div class="login-link">Already have an account? <a href="login.php">Login here</a>.</div>
    </div>
</body>
</html> 