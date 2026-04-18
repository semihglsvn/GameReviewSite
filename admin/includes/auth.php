<?php
// admin/includes/auth.php
session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2, 3])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/logger.php'; 
?>