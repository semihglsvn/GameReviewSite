<?php
// admin/get_game.php
require_once 'includes/auth.php'; 
require_once '../config/db.php'; 

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $game_id = (int)$_GET['id'];
    
    // 1. Get main game info
    $stmt = $conn->prepare("SELECT * FROM games WHERE id = ?");
    $stmt->bind_param("i", $game_id);
    $stmt->execute();
    $game = $stmt->get_result()->fetch_assoc();
    
    if ($game) {
        // 2. Get associated Genre IDs
        $g_stmt = $conn->prepare("SELECT genre_id FROM game_genres WHERE game_id = ?");
        $g_stmt->bind_param("i", $game_id);
        $g_stmt->execute();
        $genres = array_column($g_stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'genre_id');

        // 3. Get associated Platform IDs
        $p_stmt = $conn->prepare("SELECT platform_id FROM game_platforms WHERE game_id = ?");
        $p_stmt->bind_param("i", $game_id);
        $p_stmt->execute();
        $platforms = array_column($p_stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'platform_id');

        echo json_encode([
            'success' => true, 
            'game' => $game, 
            'genres' => $genres, 
            'platforms' => $platforms
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Game not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No ID provided']);
}