<?php
session_start();
require_once 'config/db.php';
require_once 'includes/mailer.php'; // Bring in our new email engine!

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "Invalid email format.";
        $msg_type = "error";
    } else {
        // 1. Check if the user exists
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Security best practice: ALWAYS say "If an account exists, an email was sent"
        // This prevents hackers from using this form to guess which emails are registered.
        $msg = "If an account with that email exists, a password reset link has been sent.";
        $msg_type = "success";

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

// 2. Generate Secure Token
            $reset_token = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $reset_token);
            
            // TIMER FIX: Set to 15 minutes (900 seconds)
            $expiry = date("Y-m-d H:i:s", time() + 900);

            // 3. Save Hash to DB
            $upd_stmt = $conn->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires = ? WHERE email = ?");
            $upd_stmt->bind_param("sss", $token_hash, $expiry, $email);
            $upd_stmt->execute();
            $upd_stmt->close();

            // 4. Build the Professional Email
            $reset_link = BASE_URL . "/reset_password.php?token=" . $reset_token . "&email=" . urlencode($email);            
            // Get the absolute path to your PNG logo for embedding
            $logo_path = __DIR__ . '/assets/images/logo.png'; 

            $subject = "Reset your GameJoint password";
            
            $body = "
            <div style='background-color: #f4f4f4; padding: 20px; font-family: Arial, sans-serif;'>
                <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.1);'>
                    <tr>
                        <td align='center' style='padding: 30px 0; background-color: #1a1a1a;'>
                            <img src='cid:logo_img' alt='GameJoint Logo' width='180' style='display: block; border: 0;'>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 40px 30px;'>
                            <h1 style='color: #333333; font-size: 22px; margin-top: 0;'>Password Reset Request</h1>
                            <p style='color: #555555; font-size: 16px; line-height: 1.6;'>Hello <strong>{$user['username']}</strong>,</p>
                            <p style='color: #555555; font-size: 16px; line-height: 1.6;'>We received a request to reset your password. Click the button below to get started:</p>
                            
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='{$reset_link}' style='background-color: #27ae60; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px; display: inline-block;'>Reset Password</a>
                            </div>
                            
                            <p style='color: #888888; font-size: 14px; line-height: 1.6;'>For security, this link will expire in <strong>15 minutes</strong>. If you didn't request this, you can safely ignore this email.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style='padding: 20px 30px; background-color: #fff9e6; border-top: 1px solid #ffeeba;'>
                            <p style='color: #856404; font-size: 12px; margin: 0;'>
                                <strong>Security Alert:</strong> If you didn't do this, please secure your email account.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align='center' style='padding: 20px; background-color: #f9f9f9; color: #999999; font-size: 12px;'>
                            <p style='margin: 0;'>&copy; 2026 GameJoint. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </div>
            ";

            // 5. Send the email with the logo path included
            $mail_status = sendGameJointEmail($email, $subject, $body, $logo_path);         
            // Uncomment this line if you need to debug why an email isn't sending:
            // if ($mail_status !== true) { echo $mail_status; }
        }
        $stmt->close();
    }
}

require_once 'includes/header.php';
?>

<div class="container main-content" style="max-width: 400px; margin: 80px auto;">
    <div style="background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);" class="settings-card">
        
        <h2 style="text-align: center; color: #333; margin-bottom: 10px;">Forgot Password</h2>
        <p style="text-align: center; color: #666; margin-bottom: 25px; font-size: 14px;">Enter your registered email address and we will send you a secure link to reset your password.</p>

        <?php if ($msg): ?>
            <div style="background: <?php echo $msg_type === 'error' ? '#f8d7da' : '#d4edda'; ?>; 
                        color: <?php echo $msg_type === 'error' ? '#721c24' : '#155724'; ?>; 
                        padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; border: 1px solid <?php echo $msg_type === 'error' ? '#f5c6cb' : '#c3e6cb'; ?>;">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div style="margin-bottom: 20px;">
                <label style="display: block; color: #555; margin-bottom: 5px; font-weight: bold;">Email Address</label>
                <input type="email" name="email" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            <button type="submit" style="width: 100%; padding: 12px; font-size: 16px; background: #34495e; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Send Reset Link</button>
        </form>

        <p style="text-align: center; margin-top: 20px; color: #666;">
            Remembered it? <a href="login.php" style="color: #007bff; text-decoration: none;">Back to Login</a>
        </p>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>