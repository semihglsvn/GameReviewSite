<?php
session_start();
require_once 'config/db.php';
require_once 'config/keys.php'; 
require_once 'includes/mailer.php';

// If user is already logged in, redirect them to the homepage
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ==========================================
    // 1. CLOUDFLARE TURNSTILE VERIFICATION
    // ==========================================
    $turnstile_secret = TURNSTILE_SECRET_KEY; 
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';
    
    if (empty($turnstile_response)) {
        $error = "Widget Error: No security token was sent. Please let the widget load.";
    } else {
        $verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        
        $verify_data = [
            'secret' => $turnstile_secret,
            'response' => $turnstile_response
        ];
        
        // HTTP settings + SSL bypass + Keep-Alive fix for XAMPP
        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n" .
                             "Connection: close\r\n",
                'method'  => 'POST',
                'content' => http_build_query($verify_data),
                'timeout' => 5 
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false
            ]
        ];
        
        $context  = stream_context_create($options);
        
        // @ suppresses PHP warnings if the network totally drops
        $verify_result = @file_get_contents($verify_url, false, $context);
        
        if ($verify_result === false) {
            $error = "Server Error: XAMPP blocked the connection to Cloudflare.";
        } else {
            $verify_json = json_decode($verify_result, true);
        
            if (!$verify_json || empty($verify_json['success'])) {
                $cf_err = isset($verify_json['error-codes']) ? implode(", ", $verify_json['error-codes']) : "Unknown";
                $error = "Cloudflare Rejected: " . $cf_err;
            } else {
                
                // ==========================================
                // 2. Sanitize Inputs (Bot Check Passed)
                // ==========================================
                $username = trim($_POST['username']);
                $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
                $dob = trim($_POST['dob']);
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];

                // 3. Validation Prep
                $dob_obj = date_create($dob);
                $now = date_create();
                $min_date = date_create('-120 years');
                
                // Strict Username Validation
                $is_valid_username = preg_match('/^[a-zA-Z0-9_]+$/', $username);

                // 4. Error Checking Chain
                if (empty($username) || empty($email) || empty($password) || empty($dob)) {
                    $error = "All fields are required.";
                } elseif (!$is_valid_username) {
                    $error = "Invalid username. You can only use letters, numbers, and underscores (no spaces or emojis).";
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = "Invalid email format.";
                } elseif ($password !== $confirm_password) {
                    $error = "Passwords do not match.";
                } elseif (strlen($password) < 6) {
                    $error = "Password must be at least 6 characters.";
                } elseif (!$dob_obj) {
                    $error = "Invalid date of birth format.";
                } elseif ($dob_obj > $now) {
                    $error = "Date of birth cannot be in the future.";
                } elseif ($dob_obj < $min_date) {
                    $error = "Please enter a realistic date of birth.";
                } else {
                    // 5. Check if Username or Email already exists
                    $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $stmt_check->bind_param("ss", $username, $email);
                    $stmt_check->execute();
                    $stmt_check->store_result();

                    if ($stmt_check->num_rows > 0) {
                        $error = "Username or Email already exists.";
                    } else {
                        
                        // ==========================================
                        // 6. Hash Password, Generate Token & Insert
                        // ==========================================
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $role_id = 5; // Default role for standard users
                        
                        // Generate a secure, random verification token
                        $verify_token = bin2hex(random_bytes(32));

                        $stmt_insert = $conn->prepare("INSERT INTO users (role_id, username, email, password_hash, dob, verification_token) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt_insert->bind_param("isssss", $role_id, $username, $email, $hashed_password, $dob, $verify_token);
                        
                        if ($stmt_insert->execute()) {
                            
                            // ==========================================
                            // 7. SEND VERIFICATION EMAIL
                            // ==========================================
                            $subject = "Verify your GameJoint Account";
                            $logo_path = __DIR__ . '/assets/images/logo.png'; 
                            
                            $verify_link = BASE_URL . "/verify.php?email=" . urlencode($email) . "&token=" . $verify_token;                            
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
                                            <h2 style='color: #333333; font-size: 24px; margin-top: 0; margin-bottom: 20px;'>Verify Your Account</h2>
                                            <p style='color: #555555; font-size: 16px; line-height: 1.6; margin-bottom: 20px;'>Hello <strong>{$username}</strong>,</p>
                                            <p style='color: #555555; font-size: 16px; line-height: 1.6; margin-bottom: 30px;'>Thanks for registering. Click the button below to verify your email address and activate your account:</p>
                                            
                                            <div style='text-align: center; margin: 35px 0;'>
                                                <a href='{$verify_link}' style='background-color: #27ae60; color: #ffffff; padding: 14px 30px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 16px; display: inline-block;'>Verify Email Address</a>
                                            </div>
                                            
                                            <p style='color: #777777; font-size: 14px; line-height: 1.6; margin-top: 30px;'>If you didn't create an account, you can safely ignore this email.</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style='padding: 20px 40px; background-color: #fffdeb; border-top: 1px solid #ffeeba;'>
                                            <p style='color: #856404; font-size: 13px; margin: 0;'>
                                                <strong>Security Alert:</strong> If you didn't do this, please secure your email account.
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

                            sendGameJointEmail($email, $subject, $body, $logo_path);

                            $success = "Registration almost complete! <strong>Please check your email</strong> to verify your account before logging in.";
                        } else {
                            $error = "Something went wrong. Please try again.";
                        }
                        $stmt_insert->close();
                    }
                    $stmt_check->close();
                }
            }
        }
    }
}

// Now load the header after logic is done
require_once 'includes/header.php';
?>

<div class="container main-content" style="max-width: 500px; margin: 60px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
    <h2 style="text-align: center; color: #333; margin-bottom: 20px;">Create an Account</h2>

    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
            <?php echo $success; ?> <br><br>
            <a href="login.php" class="btn-login" style="text-decoration: none; display: inline-block; padding: 10px 20px; background: #007bff; color: white; border-radius: 4px;">Go to Login</a>
        </div>
    <?php else: ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<form action="register.php" method="POST" onsubmit="
    const btn = this.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.style.backgroundColor = '#95a5a6';
    btn.style.cursor = 'not-allowed';
    btn.innerText = 'Sending email, please wait...';
">            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">Username</label>
                <input type="text" name="username" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">Email Address</label>
                <input type="email" name="email" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">Date of Birth</label>
                <input type="date" name="dob" max="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;" value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">Password</label>
                <input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">Confirm Password</label>
                <input type="password" name="confirm_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            
<!-- We removed cf-turnstile and added an ID and data-sitekey -->
<div id="turnstile-container" data-sitekey="<?php echo TURNSTILE_SITE_KEY; ?>" style="margin-bottom: 20px;"></div>

<script>
    // 1. Manually render the widget when Cloudflare loads
    window.onloadTurnstileCallback = function () {
        // Check local storage for the current theme
        let currentTheme = localStorage.getItem('site-theme') === 'dark' ? 'dark' : 'light';
        
        // Draw the widget and save its ID globally
        window.myTurnstileId = turnstile.render('#turnstile-container', {
            sitekey: document.getElementById('turnstile-container').getAttribute('data-sitekey'),
            theme: currentTheme
        });
    };
</script>
<!-- 2. Notice the ?onload=onloadTurnstileCallback at the end of this URL! -->
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onloadTurnstileCallback" async defer></script>            
            <button type="submit" class="btn-register" style="width: 100%; padding: 12px; font-size: 16px; border: none; cursor: pointer; background: #27ae60; color: white; border-radius: 4px;">Register Now</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px; color: #666;">
            Already have an account? <a href="login.php" style="color: #007bff; text-decoration: none;">Login here</a>
        </p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>    