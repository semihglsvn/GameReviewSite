<?php
session_start();
require_once 'config/db.php';

// If user is already logged in, redirect them
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ==========================================
    // 1. CLOUDFLARE TURNSTILE VERIFICATION
    // ==========================================
    $turnstile_secret = "0x4AAAAAADCZUqEMcIPgbKvmAq-F_8eruGs"; // <--- PASTE YOUR SECRET KEY HERE
    $turnstile_response = $_POST['cf-turnstile-response'] ?? '';
    
    if (empty($turnstile_response)) {
        $error = "Widget Error: No security token was sent. Please let the widget load.";
    } else {
        $verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
        
        // Removed 'remoteip' to prevent XAMPP IPv6 conflicts
        $verify_data = [
            'secret' => $turnstile_secret,
            'response' => $turnstile_response
        ];
        
        // HTTP settings + SSL bypass for XAMPP
// HTTP settings + SSL bypass for XAMPP
        $options = [
            'http' => [
                // THE FIX: Added "Connection: close" so PHP doesn't wait 5 seconds
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
                // Diagnostic output to see exactly why it failed
                $cf_err = isset($verify_json['error-codes']) ? implode(", ", $verify_json['error-codes']) : "Unknown";
                $error = "Cloudflare Rejected: " . $cf_err;
            } else {
                
                // ==========================================
                // 2. SANITIZATION & LOGIN LOGIC (IT PASSED!)
                // ==========================================
                $login_id = trim($_POST['login_id']);
                $password = $_POST['password'];
                $remember_me = isset($_POST['remember_me']) ? true : false;

                $is_email = filter_var($login_id, FILTER_VALIDATE_EMAIL);
                $is_valid_username = preg_match('/^[a-zA-Z0-9_]+$/', $login_id);

                if (empty($login_id) || empty($password)) {
                    $error = "Please enter both username/email and password.";
                } elseif (!$is_email && !$is_valid_username) {
                    $error = "Invalid format. Usernames can only contain letters, numbers, and underscores.";
                } else {
                    $stmt = $conn->prepare("SELECT id, username, password_hash, role_id FROM users WHERE username = ? OR email = ?");
                    $stmt->bind_param("ss", $login_id, $login_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        
                        if (password_verify($password, $user['password_hash'])) {
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['role_id'] = $user['role_id'];

                            if ($remember_me) {
                                $random_token = bin2hex(random_bytes(32)); 
                                $token_hash = hash('sha256', $random_token);
                                
                                $update_stmt = $conn->prepare("UPDATE users SET remember_token_hash = ? WHERE id = ?");
                                $update_stmt->bind_param("si", $token_hash, $user['id']);
                                $update_stmt->execute();
                                $update_stmt->close();
                                
                                $cookie_value = $user['id'] . '_' . $random_token;

                                $cookie_options = [
                                    'expires' => time() + (86400 * 30),
                                    'path' => '/',
                                    'secure' => true,     
                                    'httponly' => true,   
                                    'samesite' => 'Lax'   
                                ];
                                setcookie('remember_token', $cookie_value, $cookie_options);
                            }

                            header("Location: index.php");
                            exit;
                        } else {
                            $error = "Invalid password.";
                        }
                    } else {
                        $error = "No account found with that username or email.";
                    }
                    $stmt->close();
                }
            }
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container main-content" style="max-width: 400px; margin: 80px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
    <h2 style="text-align: center; color: #333; margin-bottom: 20px;">Welcome Back</h2>

    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    
    <form action="login.php" method="POST">
        <div style="margin-bottom: 15px;">
            <label style="display: block; color: #555; margin-bottom: 5px;">Username or Email</label>
            <input type="text" name="login_id" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;" value="<?php echo isset($_POST['login_id']) ? htmlspecialchars($_POST['login_id']) : ''; ?>">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; color: #555; margin-bottom: 5px;">Password</label>
            <input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 25px; display: flex; align-items: center;">
            <input type="checkbox" name="remember_me" id="remember_me" style="margin-right: 8px; cursor: pointer;">
            <label for="remember_me" style="color: #555; cursor: pointer; user-select: none;">Remember Me</label>
        </div>

        <div class="cf-turnstile" data-sitekey="0x4AAAAAADCZUgFHjIE8Oqqn" data-theme="auto" style="margin-bottom: 20px;"></div> 

        <button type="submit" class="btn-login" style="width: 100%; padding: 12px; font-size: 16px; border: none; cursor: pointer;">Log In</button>
    </form>

    <div style="text-align: center; margin-top: 15px;">
    <a href="forgot_password.php" style="color: #7f8c8d; text-decoration: none; font-size: 14px; transition: color 0.2s;" onmouseover="this.style.color='#007bff'" onmouseout="this.style.color='#7f8c8d'">Forgot Password?</a>
</div>

    <p style="text-align: center; margin-top: 20px; color: #666;">
        Don't have an account? <a href="register.php" style="color: #007bff; text-decoration: none;">Register here</a>
    </p>
</div>

<?php require_once 'includes/footer.php'; ?>