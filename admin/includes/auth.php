<?php
// admin/includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- FIX FOR THE DASHBOARD GLITCH ---
// 1. Kick out anyone who isn't an admin. If they fail this check, STOP THE SCRIPT.
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
    header("Location: login.php");
    exit; // <--- THIS EXIT IS MANDATORY. It prevents the dashboard from loading in the background!
}

// ==========================================
// 2. INACTIVITY TIMEOUT LOGIC
// ==========================================
$timeout_duration = 1800; // 30 minutes

if (isset($_SESSION['last_activity'])) {
    $elapsed_time = time() - $_SESSION['last_activity'];

    if ($elapsed_time > $timeout_duration) {
        // They took too long! Burn the session down.
        session_unset();
        session_destroy();
        setcookie('remember_token', '', time() - 3600, '/');

        // Redirect to login with a timeout message
        header("Location: login.php?timeout=1");
        exit; // <--- THIS EXIT IS MANDATORY
    }
}

// 3. Update the activity timestamp to RIGHT NOW because they just clicked something
$_SESSION['last_activity'] = time();

// Load your original logging functions
require_once __DIR__ . '/logger.php';
// 
?>