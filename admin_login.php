<?php
session_start();

// Hardcoded admin credentials
$admin_username = 'sjhx';
$admin_password = '45skxjbin1sdxj23';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['is_admin'] = true;
        header('Location: admin_dashboard.php');
        exit();
    } else {
        $message = 'Invalid admin credentials.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
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
        .admin-login-card {
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
        .admin-login-card:before {
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
        .admin-login-card h2 {
            margin-top: 18px;
            margin-bottom: 18px;
            color: #2c3e50;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .admin-login-card label {
            font-weight: 500;
            color: #555;
        }
        .admin-login-card input[type="text"],
        .admin-login-card input[type="password"] {
            width: 100%;
            margin-bottom: 18px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 1rem;
        }
        .admin-login-card button {
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
        .admin-login-card button:hover {
            background: #217dbb;
        }
        .admin-login-card .message {
            color: #e74c3c;
            margin: 10px 0 0 0;
            font-size: 1rem;
            min-height: 22px;
        }
        .admin-login-card .back-link {
            margin-top: 18px;
            font-size: 0.98rem;
        }
        .admin-login-card .back-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        .admin-login-card .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="admin-login-card">
        <h2>Admin Login</h2>
        <form method="post">
            <label>Username:</label><br>
            <input type="text" name="username" required><br>
            <label>Password:</label><br>
            <input type="password" name="password" required><br>
            <button type="submit">Login</button>
        </form>
        <div class="message"><?php echo $message; ?></div>
        <div class="back-link"><a href="index.php">Back to Home</a></div>
    </div>
</body>
</html> 
