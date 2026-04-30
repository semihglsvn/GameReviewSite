<?php
session_start();
require_once 'config/db.php';
require_once 'includes/mailer.php';
require_once 'config/keys.php';

$msg = "";
$msg_type = "";

// Grab the username/email from the URL if it exists
$identifier = $_GET['email'] ?? ''; 

// ONLY run the email logic if they clicked the Submit button (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_email'])) {
    
    $identifier = trim($_POST['resend_email']); 

    // Search for the user by BOTH email and username
    $stmt = $conn->prepare("SELECT id, username, email, is_verified FROM users WHERE email = ? OR username = ?");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $actual_email = $user['email']; 
        
        if ($user['is_verified'] == 0) {
            
            // Generate a new secure token
            $verify_token = bin2hex(random_bytes(32));
            
            // Update the database
            $upd = $conn->prepare("UPDATE users SET verification_token = ? WHERE id = ?");
            $upd->bind_param("si", $verify_token, $user['id']);
            
            if ($upd->execute()) {
                
                // ==========================================
                // SEND PROFESSIONAL HTML EMAIL
                // ==========================================
                $subject = "Verify your GameJoint Account";
                $logo_path = __DIR__ . '/assets/images/logo.png'; 
                $verify_link = BASE_URL . "/verify.php?email=" . urlencode($actual_email) . "&token=" . $verify_token;                
$body = "
                <div style='background-color: #f4f4f4; padding: 40px 20px; font-family: Arial, sans-serif;'>
                    <table align='center' border='0' cellpadding='0' cellspacing='0' width='600' style='background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);'>
                        <tr>
                            <td align='center' style='padding: 40px 0; background-color: #222222;'>
                                <img src='cid:logo_img' alt='GameJoint Logo' width='200' style='display: block; border: 0;'>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 40px 40px 20px 40px;'>
                                <h2 style='color: #333333; font-size: 24px; margin-top: 0; margin-bottom: 20px;'>New Verification Link</h2>
                                <p style='color: #555555; font-size: 16px; line-height: 1.6; margin-bottom: 20px;'>Hello <strong>{$user['username']}</strong>,</p>
                                <p style='color: #555555; font-size: 16px; line-height: 1.6; margin-bottom: 30px;'>You requested a new verification link. Click the button below to activate your GameJoint account:</p>
                                
                                <div style='text-align: center; margin: 35px 0;'>
                                    <a href='{$verify_link}' style='background-color: #27ae60; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px; display: inline-block;'>Verify Email Address</a>
                                </div>
                                
                                <p style='color: #777777; font-size: 14px; line-height: 1.6; margin-top: 30px;'>If you didn't request this link, you can safely ignore this email.</p>
                            </td>
                        </tr>
                        <tr>
                            <td style='padding: 20px 40px; background-color: #fffdeb; border-top: 1px solid #ffeeba;'>
                                <p style='color: #856404; font-size: 13px; margin: 0;'>
                                    <strong>Security Alert:</strong> If you didn't request this, please secure your email account.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <td align='center' style='padding: 20px 40px; background-color: #f9f9f9; color: #aaaaaa; font-size: 12px; border-top: 1px solid #eeeeee;'>
                                <p style='margin: 0;'>&copy; " . date('Y') . " GameJoint. All rights reserved.</p>
                            </td>
                        </tr>
                    </table>
                </div>
                ";

                sendGameJointEmail($actual_email, $subject, $body, $logo_path);
                
                $msg = "A new verification link has been sent to <strong>" . htmlspecialchars($actual_email) . "</strong>!";
                $msg_type = "success";
            } else {
                $msg = "Database error. Please try again.";
                $msg_type = "error";
            }
        } else {
            $msg = "This account is already verified! You can just log in.";
            $msg_type = "success";
        }
    } else {
        $msg = "Account not found.";
        $msg_type = "error";
    }
    $stmt->close();
}

require_once 'includes/header.php';
?>

<div class="container main-content" style="max-width: 500px; margin: 80px auto; text-align: center;">
    <div style="background: #fff; padding: 40px 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        
        <h2 style="color: #333; margin-bottom: 20px;">Resend Verification</h2>
        
        <?php if ($msg): ?>
            <div style="background: <?php echo $msg_type === 'error' ? '#f8d7da' : '#d4edda'; ?>; 
                        color: <?php echo $msg_type === 'error' ? '#721c24' : '#155724'; ?>; 
                        padding: 15px; border-radius: 4px; margin-bottom: 25px; border: 1px solid <?php echo $msg_type === 'error' ? '#f5c6cb' : '#c3e6cb'; ?>;">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <?php if ($msg_type !== 'success'): ?>
            <form action="resend_verification.php" method="POST" onsubmit="
                const btn = this.querySelector('button[type=submit]');
                btn.disabled = true;
                btn.style.backgroundColor = '#95a5a6';
                btn.style.cursor = 'not-allowed';
                btn.innerText = 'Sending email, please wait...';
            ">
                <p style="color: #666; margin-bottom: 20px; font-size: 15px;">Click the button below to resend the verification email to your account.</p>
                
                <input type="hidden" name="resend_email" value="<?php echo htmlspecialchars($identifier); ?>">
                
                <button type="submit" style="background: #27ae60; color: white; border: none; padding: 12px 20px; font-size: 16px; border-radius: 4px; cursor: pointer; width: 100%; margin-bottom: 15px; font-weight: bold;">Send Verification Email</button>
            </form>
        <?php endif; ?>

        <div style="margin-top: 15px;">
            <a href="login.php" style="color: #007bff; text-decoration: none; font-weight: bold;">Return to Login</a>
        </div>
        
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>