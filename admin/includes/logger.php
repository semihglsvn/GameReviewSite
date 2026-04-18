<?php
// admin/includes/logger.php

function logAdminAction($conn, $action_type, $details) {
    $admin_id = $_SESSION['user_id'] ?? null;
    $admin_username = $_SESSION['username'] ?? 'System';

    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, admin_username, action_type, details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $admin_id, $admin_username, $action_type, $details);
    $stmt->execute();
}

// Tailored to your exact system_logs table
function logSystemError($conn, $error_message, $error_type = 'ERROR') {
    // Grab the user's IP address securely
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

    $stmt = $conn->prepare("INSERT INTO system_logs (error_type, error_message, ip_address) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $error_type, $error_message, $ip_address);
    $stmt->execute();
}
?>