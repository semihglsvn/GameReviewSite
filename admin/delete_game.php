<?php
// admin/delete_game.php
require_once 'includes/auth.php'; 
require_once '../config/db.php'; 

header('Content-Type: application/json');

// Only Admins and Editors can delete games
if ($_SESSION['role_id'] == 3) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Depending on how your frontend sends the ID, it might be in $_POST['id'] or $_POST['game_id']
    $game_id = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_POST['game_id']) ? (int)$_POST['game_id'] : 0);

    if ($game_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid Game ID.']);
        exit;
    }

    try {
        // 1. Grab the title BEFORE we delete it so we can put it in the log
        $t_stmt = $conn->prepare("SELECT title FROM games WHERE id = ?");
        $t_stmt->bind_param("i", $game_id);
        $t_stmt->execute();
        $res = $t_stmt->get_result()->fetch_assoc();
        $game_title = $res ? $res['title'] : "Unknown Game";

        // 2. Delete the game 
        // (Because of ON DELETE CASCADE, this will also automatically wipe out 
        // the connected platforms, genres, and reviews in the database!)
        $stmt = $conn->prepare("DELETE FROM games WHERE id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();

        // 3. Log the successful deletion
        logAdminAction($conn, 'DELETE_GAME', "Deleted game '$game_title' (ID: $game_id)");

        echo json_encode(['success' => true]);

    } catch (Exception $e) {
        // 4. If something crashes, log the error and warn the user
        logSystemError($conn, "Failed to delete game ID $game_id: " . $e->getMessage(), 'DATABASE_ERROR');
        echo json_encode(['success' => false, 'error' => 'A database error occurred while deleting the game.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
}