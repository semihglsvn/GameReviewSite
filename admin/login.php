<?php
// admin/login.php
session_start();
require_once '../config/db.php'; 

// --- FIX FOR THE INFINITE REDIRECT LOOP ---
if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role_id']) && in_array($_SESSION['role_id'], [1, 2, 3])) {
        // They are an Admin/Editor -> Send them to the Admin Dashboard
        header("Location: index.php"); 
        exit;
    } else {
        // They are a Regular User -> Kick them back to the Public Homepage!
        header("Location: ../index.php"); 
        exit;
    }
}

$error = '';

// Check if they were kicked out for inactivity
if (isset($_GET['timeout']) && $_GET['timeout'] == 1) {
    $error = "Your session expired due to inactivity. Please log in again.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_id = trim($_POST['login_id']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT id, username, password_hash, role_id FROM users WHERE (email = ? OR username = ?) AND role_id IN (1, 2, 3)");
    $stmt->bind_param("ss", $login_id, $login_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password_hash'])) {
            
            // Prevent Session Fixation attacks
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['username'] = $user['username'];
            
            // Start the inactivity timer
            $_SESSION['last_activity'] = time();
            
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid credentials or access denied.";
        }
    } else {
        $error = "Invalid credentials or access denied.";
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
            <div class="login-error" style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center;">
                <?php echo htmlspecialchars($error); ?>
            </div>
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