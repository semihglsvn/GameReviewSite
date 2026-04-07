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
    // Sanitize (Block JS)
    $login_id = htmlspecialchars(strip_tags(trim($_POST['login_id'])));
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']) ? true : false;

    if (empty($login_id) || empty($password)) {
        $error = "Please enter both username/email and password.";
    } else {
        // Prepared Statement (Block SQLi)
        $stmt = $conn->prepare("SELECT id, username, password_hash, role_id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $login_id, $login_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Secure Password Verification
            if (password_verify($password, $user['password_hash'])) {
                // Set Session Variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role_id'] = $user['role_id'];

                // ==========================================
                // REMEMBER ME COOKIE LOGIC
                // ==========================================
                if ($remember_me) {
                    // Create a secure, unforgeable token using their DB data
                    $secure_hash = md5($user['username'] . $user['password_hash']);
                    $token = $user['id'] . '-' . $secure_hash;
                    
                    // Set cookie to expire in 30 days (86400 seconds * 30)
                    setcookie('remember_token', $token, time() + (86400 * 30), "/");
                }

                // Success! Redirect to homepage
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

// Load header after logic
require_once 'includes/header.php';
?>

<div class="container main-content" style="max-width: 400px; margin: 80px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
    <h2 style="text-align: center; color: #333; margin-bottom: 20px;">Welcome Back</h2>

    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div style="margin-bottom: 15px;">
            <label style="display: block; color: #555; margin-bottom: 5px;">Username or Email</label>
            <input type="text" name="login_id" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
        </div>

        <div style="margin-bottom: 15px;">
            <label style="display: block; color: #555; margin-bottom: 5px;">Password</label>
            <input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
        </div>

        <!-- NEW: Remember Me Checkbox -->
        <div style="margin-bottom: 25px; display: flex; align-items: center;">
            <input type="checkbox" name="remember_me" id="remember_me" style="margin-right: 8px; cursor: pointer;">
            <label for="remember_me" style="color: #555; cursor: pointer; user-select: none;">Remember Me</label>
        </div>

        <button type="submit" class="btn-login" style="width: 100%; padding: 12px; font-size: 16px; border: none; cursor: pointer;">Log In</button>
    </form>

    <p style="text-align: center; margin-top: 20px; color: #666;">
        Don't have an account? <a href="register.php" style="color: #007bff; text-decoration: none;">Register here</a>
    </p>
</div>

<?php require_once 'includes/footer.php'; ?>