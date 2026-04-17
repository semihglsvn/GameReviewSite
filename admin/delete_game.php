<?php
// admin/delete_game.php
require_once 'includes/auth.php'; 
require_once '../config/db.php'; 

// 1. Strict Role Check (Super Admin ONLY)
if ($_SESSION['role_id'] != 1) {
    die("Unauthorized action. Only Super Admins can delete games.");
}

// 2. Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_id'])) {
    $game_id = (int)$_POST['game_id'];

    // 3. Delete the game. 
    // Note: Because you set up Foreign Keys with ON DELETE CASCADE in your database, 
    // deleting the game will automatically delete its reviews, genres, and platform links!
    $stmt = $conn->prepare("DELETE FROM games WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    
    if ($stmt->execute()) {
        // Redirect back to the referring page so they don't lose their search/sort state
        $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'games.php';
        header("Location: " . $redirect_url);
        exit;
    } else {
        die("Error deleting game. Please contact support.");
    }
} else {
    header("Location: games.php");
    exit;
}