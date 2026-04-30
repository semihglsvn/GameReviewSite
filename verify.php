<?php
session_start();
require_once 'config/db.php';
require_once 'includes/header.php';
require_once 'config/keys.php';

$msg = "";
$msg_type = "";

if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = $_GET['email'];
    $token = $_GET['token'];

    // Find the user with this exact email and token
    $stmt = $conn->prepare("SELECT id, is_verified FROM users WHERE email = ? AND verification_token = ? LIMIT 1");
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if ($user['is_verified'] == 0) {
            // Flip the switch to verified and clear the token so it can't be reused!
            $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
            $update_stmt->bind_param("i", $user['id']);
            
            if ($update_stmt->execute()) {
                $msg = "Account successfully verified! You can now log in.";
                $msg_type = "success";
            } else {
                $msg = "Database error. Please try again.";
                $msg_type = "error";
            }
            $update_stmt->close();
        } else {
            $msg = "This account is already verified.";
            $msg_type = "success"; // It's technically a success if they are already verified
        }
    } else {
        $msg = "Invalid or expired verification link.";
        $msg_type = "error";
    }
    $stmt->close();
} else {
    $msg = "No verification token provided.";
    $msg_type = "error";
}
?>

<div class="container main-content" style="max-width: 400px; margin: 80px auto; text-align: center;">
    <div style="background: #fff; padding: 40px 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        
        <h2 style="color: #333; margin-bottom: 20px;">Email Verification</h2>
        
        <div style="background: <?php echo $msg_type === 'error' ? '#f8d7da' : '#d4edda'; ?>; 
                    color: <?php echo $msg_type === 'error' ? '#721c24' : '#155724'; ?>; 
                    padding: 15px; border-radius: 4px; margin-bottom: 25px; border: 1px solid <?php echo $msg_type === 'error' ? '#f5c6cb' : '#c3e6cb'; ?>;">
            <?php echo $msg; ?>
        </div>

        <a href="login.php" style="display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">Go to Login</a>
        
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>