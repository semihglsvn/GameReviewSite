<?php
session_start();
require_once 'config/db.php';

$msg = '';
$msg_type = '';
$show_form = false;

// 1. Capture the Token and Email from the URL
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (empty($token) || empty($email)) {
    $msg = "Invalid or missing reset link.";
    $msg_type = "error";
} else {
    // 2. Verify the token in the database
    // We hash the raw token from the URL to compare it with the stored hash
// 2. Verify the token in the database
    $token_hash = hash('sha256', $token);
    
    // We remove the "AND expires > NOW()" part and check it manually in PHP
    $stmt = $conn->prepare("SELECT id, reset_token_expires FROM users WHERE email = ? AND reset_token_hash = ?");
    $stmt->bind_param("ss", $email, $token_hash);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Convert the DB string back into a timestamp
        $expiry_time = strtotime($user['reset_token_expires']);
        $current_time = time();

        // Check if the link is still valid (Current time is less than expiry)
        if ($expiry_time > $current_time) {
            $show_form = true;
        } else {
            $msg = "This link has expired. Please request a new one.";
            $msg_type = "error";
        }
    } else {
        $msg = "This link is invalid. Please make sure you copied the full URL.";
        $msg_type = "error";
    }
}

// 3. Handle the Password Reset Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $show_form) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($new_password) < 6) {
        $msg = "Password must be at least 6 characters.";
        $msg_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $msg = "Passwords do not match.";
        $msg_type = "error";
    } else {
        // Hash the new password securely
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update DB: Set new password and CLEAR the reset token so it can't be used again
        $upd_stmt = $conn->prepare("UPDATE users SET password_hash = ?, reset_token_hash = NULL, reset_token_expires = NULL WHERE id = ?");
        $upd_stmt->bind_param("si", $new_hash, $user['id']);
        
        if ($upd_stmt->execute()) {
            $msg = "Password successfully reset! You can now log in.";
            $msg_type = "success";
            $show_form = false; // Hide the form after success to prevent double submission
        } else {
            $msg = "Something went wrong. Please try again.";
            $msg_type = "error";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container main-content" style="max-width: 400px; margin: 80px auto;">
    <div style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);" class="settings-card">
        
        <h2 style="text-align: center; color: #333; margin-bottom: 25px;">Set New Password</h2>

        <?php if ($msg): ?>
            <div style="background: <?php echo $msg_type === 'error' ? '#f8d7da' : '#d4edda'; ?>; 
                        color: <?php echo $msg_type === 'error' ? '#721c24' : '#155724'; ?>; 
                        padding: 12px; border-radius: 4px; margin-bottom: 20px; text-align: center; border: 1px solid <?php echo $msg_type === 'error' ? '#f5c6cb' : '#c3e6cb'; ?>;">
                <?php echo $msg; ?>
                <?php if($msg_type === 'success'): ?>
                    <br><br><a href="login.php" style="color:#155724; font-weight:bold; text-decoration: underline;">Go to Login</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($show_form): ?>
            <form method="POST">
                <div style="margin-bottom: 15px;">
                    <label style="display: block; color: #555; margin-bottom: 5px; font-weight: bold;">New Password</label>
                    <input type="password" name="new_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div style="margin-bottom: 25px;">
                    <label style="display: block; color: #555; margin-bottom: 5px; font-weight: bold;">Confirm New Password</label>
                    <input type="password" name="confirm_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
                </div>
                <button type="submit" style="width: 100%; padding: 12px; background: #27ae60; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 16px;">Update Password</button>
            </form>
        <?php endif; ?>

        <?php if (!$show_form && $msg_type === 'error'): ?>
            <div style="text-align: center; margin-top: 15px;">
                <a href="forgot_password.php" style="color: #007bff; text-decoration: none;">Request a new reset link</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>