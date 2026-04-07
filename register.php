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
    // 1. Sanitize Inputs (Block JS / HTML Injection)
    $username = htmlspecialchars(strip_tags(trim($_POST['username'])));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $dob = htmlspecialchars(strip_tags(trim($_POST['dob'])));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 2. Validation
    $dob_obj = date_create($dob);
    $now = date_create();
    $min_date = date_create('-120 years');

    if (empty($username) || empty($email) || empty($password) || empty($dob)) {
        $error = "All fields are required.";
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
        // 3. Check if Username or Email already exists (Prepared Statement blocks SQLi)
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error = "Username or Email already exists.";
        } else {
            // 4. Hash Password & Insert
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
        <form action="register.php" method="POST">
            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">Username</label>
                <input type="text" name="username" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">Email Address</label>
                <input type="email" name="email" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">Date of Birth</label>
                <!-- Added max attribute to prevent HTML future date selection natively -->
                <input type="date" name="dob" max="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">Password</label>
                <input type="password" name="password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 25px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">Confirm Password</label>
                <input type="password" name="confirm_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>

            <button type="submit" class="btn-register" style="width: 100%; padding: 12px; font-size: 16px; border: none; cursor: pointer;">Register Now</button>
        </form>
        
        <p style="text-align: center; margin-top: 20px; color: #666;">
            Already have an account? <a href="login.php" style="color: #007bff; text-decoration: none;">Login here</a>
        </p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>