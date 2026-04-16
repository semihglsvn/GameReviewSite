<?php
// admin/includes/auth.php
session_start();

// Check if user is logged in AND has an admin-level role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
    // Not an admin? Kick them to the login page
    header("Location: login.php");
    exit;
}
?>