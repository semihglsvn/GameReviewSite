<?php
session_start();
require_once 'config/db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Fetch current user details
$stmt = $conn->prepare("SELECT email, password_hash FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ==========================================
    // ACTION 1: UPDATE EMAIL
    // ==========================================
    if (isset($_POST['action']) && $_POST['action'] === 'update_email') {
        $new_email = filter_var(trim($_POST['new_email']), FILTER_SANITIZE_EMAIL);
        $current_password = $_POST['current_password'];

        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error_msg = "Invalid email format.";
        } elseif (!password_verify($current_password, $user_data['password_hash'])) {
            $error_msg = "Incorrect current password.";
        } else {
            // Check if email is already taken by someone else
            $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_stmt->bind_param("si", $new_email, $user_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error_msg = "That email is already in use by another account.";
            } else {
                $upd_stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                $upd_stmt->bind_param("si", $new_email, $user_id);
                if ($upd_stmt->execute()) {
                    $success_msg = "Email successfully updated!";
                    $user_data['email'] = $new_email; // Update local variable for UI
                } else {
                    $error_msg = "Database error. Could not update email.";
                }
                $upd_stmt->close();
            }
            $check_stmt->close();
        }
    }

    // ==========================================
    // ACTION 2: UPDATE PASSWORD
    // ==========================================
    if (isset($_POST['action']) && $_POST['action'] === 'update_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (!password_verify($current_password, $user_data['password_hash'])) {
            $error_msg = "Incorrect current password.";
        } elseif ($new_password !== $confirm_password) {
            $error_msg = "New passwords do not match.";
        } elseif (strlen($new_password) < 6) {
            $error_msg = "New password must be at least 6 characters long.";
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $upd_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $upd_stmt->bind_param("si", $new_hash, $user_id);
            if ($upd_stmt->execute()) {
                $success_msg = "Password successfully updated!";
                $user_data['password_hash'] = $new_hash; // Update local variable
            } else {
                $error_msg = "Database error. Could not update password.";
            }
            $upd_stmt->close();
        }
    }

    // ==========================================
    // ACTION 3: DELETE ACCOUNT
    // ==========================================
    if (isset($_POST['action']) && $_POST['action'] === 'delete_account') {
        $current_password = $_POST['delete_password'];
        $confirm_checkbox = isset($_POST['confirm_delete']);

        if (!$confirm_checkbox) {
            $error_msg = "You must check the confirmation box to delete your account.";
        } elseif (!password_verify($current_password, $user_data['password_hash'])) {
            $error_msg = "Incorrect password. Account deletion aborted.";
        } else {
            // Delete user's reviews first to maintain DB integrity (if no cascading deletes exist)
            $conn->query("DELETE FROM reviews WHERE user_id = " . $user_id);
            
            // Delete the user
            $del_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $del_stmt->bind_param("i", $user_id);
            $del_stmt->execute();
            
            // Destroy session & cookies
            session_destroy();
            setcookie('remember_token', '', time() - 3600, "/");
            header("Location: index.php");
            exit;
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container main-content" style="max-width: 600px; margin: 40px auto;">
    
    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
        <a href="profile.php" style="color: #007bff; text-decoration: none; font-weight: bold;">&larr; Back to Profile</a>
        <h1 style="margin: 0; flex-grow: 1;">Account Settings</h1>
    </div>

    <?php if ($error_msg): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
            <strong>Error:</strong> <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
        <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
            <strong>Success:</strong> <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid #eee;" class="settings-card">
        <h3 style="margin-top: 0; color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px;">Update Email Address</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update_email">
            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">New Email Address</label>
                <input type="email" name="new_email" value="<?php echo htmlspecialchars($user_data['email']); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">Current Password (for security)</label>
                <input type="password" name="current_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            <button type="submit" style="background: #27ae60; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Save New Email</button>
        </form>
    </div>

    <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; border: 1px solid #eee;" class="settings-card">
        <h3 style="margin-top: 0; color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px;">Change Password</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update_password">
            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">Current Password</label>
                <input type="password" name="current_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">New Password</label>
                <input type="password" name="new_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">Confirm New Password</label>
                <input type="password" name="confirm_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            <button type="submit" style="background: #34495e; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Update Password</button>
        </form>
    </div>

    <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e74c3c;" class="settings-card danger-zone">
        <h3 style="margin-top: 0; color: #e74c3c; border-bottom: 1px solid #ffeeba; padding-bottom: 10px;">Danger Zone: Delete Account</h3>
        <p style="color: #666; font-size: 14px;">Once you delete your account, there is no going back. All your reviews and data will be permanently erased.</p>
        
        <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This cannot be undone.');">
            <input type="hidden" name="action" value="delete_account">
            
            <div style="margin-bottom: 15px;">
                <label style="display: block; color: #555; margin-bottom: 5px;">Enter Password to Verify</label>
                <input type="password" name="delete_password" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 15px; display: flex; align-items: center;">
                <input type="checkbox" name="confirm_delete" id="confirm_delete" required style="margin-right: 10px; cursor: pointer;">
                <label for="confirm_delete" style="color: #e74c3c; cursor: pointer; font-weight: bold;">I understand that this action is permanent.</label>
            </div>
            
            <button type="submit" style="background: #e74c3c; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Delete My Account</button>
        </form>
    </div>

</div>

<style>
    body.dark-mode .settings-card {
        background-color: #1e1e1e !important;
        border-color: #333 !important;
    }
    body.dark-mode .settings-card h3 {
        color: #ffffff !important;
        border-bottom-color: #333 !important;
    }
    body.dark-mode .danger-zone {
        border-color: #c0392b !important;
    }
    body.dark-mode .danger-zone h3 {
        color: #ff6b6b !important;
        border-bottom-color: #552222 !important;
    }
</style>

<?php require_once 'includes/footer.php'; ?>