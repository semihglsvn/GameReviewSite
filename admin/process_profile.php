<?php
session_start();
require_once '../config/db.php';
require_once 'includes/logger.php'; // Include your logger!

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'] ?? '';

    if ($action === 'update_info') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);

        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $stmt->bind_param("ssi", $username, $email, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Profil bilgileri başarıyla güncellendi!";
            $_SESSION['username'] = $username;
            
            // Log the profile update
            logAdminAction($conn, 'PROFILE_UPDATE', "Updated their username to '{$username}' and email to '{$email}'");
        } else {
            $_SESSION['error'] = "Bir hata oluştu.";
            logSystemError($conn, "Profile update failed for Admin ID: {$user_id}. Error: " . $conn->error, "DB_ERROR");
        }
        $stmt->close();
    }

    if ($action === 'update_password') {
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Verify old password
        if (password_verify($old_password, $result['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            $_SESSION['message'] = "Şifreniz başarıyla değiştirildi!";
            
            // Log successful password change
            logAdminAction($conn, 'PASSWORD_CHANGE', "Successfully changed their password.");
        } else {
            $_SESSION['error'] = "Mevcut şifreniz yanlış!";
            
            // Log the failed password attempt (Security Risk!)
            logAdminAction($conn, 'FAILED_PASSWORD_ATTEMPT', "Attempted to change password but entered the wrong current password.");
        }
    }

    header("Location: profile.php");
    exit;
}