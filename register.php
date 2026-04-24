<?php
session_start();
require_once 'config/db.php';

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
    $turnstile_secret = "0x4AAAAAADCZUqEMcIPgbKvmAq-F_8eruGs"; // <--- PASTE YOUR SECRET KEY HERE
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
                        // 6. Hash Password & Insert
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $role_id = 5; // Default role for standard users

                        $stmt_insert = $conn->prepare("INSERT INTO users (role_id, username, email, password_hash, dob) VALUES (?, ?, ?, ?, ?)");
                        $stmt_insert->bind_param("issss", $role_id, $username, $email, $hashed_password, $dob);
                        
                        if ($stmt_insert->execute()) {
                            $success = "Registration successful! You can now login.";
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
            <a href="login.php" class="btn-login" style="text-decoration: none;">Go to Login</a>
        </div>
    <?php else: ?>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <form action="register.php" method="POST">
            <div style="margin-bottom: 15px;">
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
            
            <div class="cf-turnstile" data-sitekey="0x4AAAAAADCZUgFHjIE8Oqqn" data-theme="auto" style="margin-bottom: 20px;"></div> 
            
            <button type="submit" class="btn-register" style="width: 100%; padding: 12px; font-size: 16px; border: none; cursor: pointer;">Register Now</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px; color: #666;">
            Already have an account? <a href="login.php" style="color: #007bff; text-decoration: none;">Login here</a>
        </p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>