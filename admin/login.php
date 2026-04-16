<?php
// admin/login.php

session_start();
require_once '../config/db.php'; 

// If already logged in as admin, send straight to dashboard
if (isset($_SESSION['user_id']) && in_array($_SESSION['role_id'], [1, 2, 3])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // We now accept either username or email in this single field
    $login_id = trim($_POST['login_id']);
    $password = trim($_POST['password']);

    // Check for email OR username, ensuring they are an admin role (1, 2, or 3)
    $stmt = $conn->prepare("SELECT id, username, password_hash, role_id FROM users WHERE (email = ? OR username = ?) AND role_id IN (1, 2, 3)");    // We bind $login_id twice because of the two '?' in the query
    $stmt->bind_param("ss", $login_id, $login_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password_hash'])) {            // Password is correct, set sessions
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['username'] = $user['username'];
            
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Admin account not found or access denied.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - GameDb</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="login-body">
    <div class="login-box">
        <h2>Admin Portal</h2>
        
        <?php if ($error): ?>
            <div class="login-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="login-form-group">
                <label>Username or Email</label>
                <input type="text" name="login_id" required autofocus>
            </div>
            
            <div class="login-form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Login</button>
        </form>
    </div>
</body>
</html>