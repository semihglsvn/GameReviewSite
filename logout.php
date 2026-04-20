<?php
session_start();

// We need the database connection so we can wipe their token
require_once __DIR__ . '/config/db.php'; 

// 1. WIPE THE TOKEN FROM THE DATABASE (Crucial Security Step)
// We do this BEFORE destroying the session so we still know their user_id
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("UPDATE users SET remember_token_hash = NULL WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}

// 2. Unset all of the session variables
$_SESSION = array();

// 3. Destroy the session entirely
session_destroy();

// 4. Delete the "Remember Me" cookie from their browser
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// 5. Redirect the user safely back to the homepage
header("Location: index.php");
exit;
?>