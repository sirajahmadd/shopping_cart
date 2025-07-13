<?php
session_start();
include 'config.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: index.php');
            exit();
        } else {
            $message = 'Invalid password.';
        }
    } else {
        $message = 'User not found.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            /* Shopping-themed gradient background */
            background: linear-gradient(120deg, #f7cac9 0%, #92a8d1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        .login-card {
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
        .login-card:before {
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
        .login-card h2 {
            margin-top: 18px;
            margin-bottom: 18px;
            color: #2c3e50;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .login-card label {
            font-weight: 500;
            color: #555;
        }
        .login-card input[type="text"],
        .login-card input[type="password"] {
            width: 100%;
            margin-bottom: 18px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
        }
        .login-card button {
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
        .login-card button:hover {
            background: #217dbb;
        }
        .login-card .message {
            color: #e74c3c;
            margin: 10px 0 0 0;
            font-size: 1rem;
            min-height: 22px;
        }
        .login-card .register-link {
            margin-top: 18px;
            font-size: 0.98rem;
        }
        .login-card .register-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        .login-card .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2>Login</h2>
        <form method="post">
            <label>Username:</label><br>
            <input type="text" name="username" required><br>
            <label>Password:</label><br>
            <input type="password" name="password" required><br>
            <button type="submit">Login</button>
        </form>
        <div class="message"><?php echo $message; ?></div>
        <div class="register-link">Don't have an account? <a href="register.php">Register here</a>.</div>
    </div>
</body>
</html> 