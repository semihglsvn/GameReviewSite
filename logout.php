<?php
session_start();

// 1. Unset all of the session variables
$_SESSION = array();

// 2. Destroy the session entirely
session_destroy();

// 3. Delete the "Remember Me" cookie by setting its expiration time to the past
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// 4. Redirect the user safely back to the homepage
header("Location: index.php");
exit;
?>