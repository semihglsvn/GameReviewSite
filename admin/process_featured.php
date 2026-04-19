<?php
// admin/process_featured.php
require_once 'includes/auth.php';
require_once '../config/db.php';

header('Content-Type: application/json');

// Only Admins and Editors
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($_SESSION['role_id'], [1, 2])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    // ==========================================
    // 1. ADD ACTION (NOW APPENDS TO END)
    // ==========================================
  // ==========================================
    // 1. ADD ACTION
    // ==========================================
    if ($action === 'add') {
        $game_id = (int)$_POST['game_id'];

        // NEW: Verify the game actually exists in the database
        $verify = $conn->query("SELECT id FROM games WHERE id = $game_id");
        if ($verify->num_rows === 0) {
            echo json_encode(['success' => false, 'error' => "Game ID #$game_id does not exist in the database."]);
            exit;
        }

        // 1. Check current total limit (max 16)
        $count_check = $conn->query("SELECT COUNT(id) FROM featured_games")->fetch_row();
        if ($count_check[0] >= 16) {
            echo json_encode(['success' => false, 'error' => 'Slider is full (max 16). Remove a game first.']);
            exit;
        }

        // 2. Ensure this game isn't already featured
        $check = $conn->query("SELECT id FROM featured_games WHERE game_id = $game_id");
        if ($check->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'This game is already featured!']);
            exit;
        }

        // 3. Find the NEXT display order number (MAX + 1)
        $order_query = $conn->query("SELECT COALESCE(MAX(display_order), 0) + 1 FROM featured_games");
        $next_order = $order_query->fetch_row()[0];

        $stmt = $conn->prepare("INSERT INTO featured_games (game_id, display_order) VALUES (?, ?)");
        $stmt->bind_param("ii", $game_id, $next_order);
        $stmt->execute();
        
        logAdminAction($conn, 'ADD_FEATURED', "Added Game ID $game_id to slider at position $next_order");
        echo json_encode(['success' => true]);

    // ==========================================

    // ==========================================
    // 2. DELETE SINGLE ACTION
    // ==========================================
    } elseif ($action === 'delete') {
        $feature_id = (int)$_POST['feature_id'];
        
        $stmt = $conn->prepare("DELETE FROM featured_games WHERE id = ?");
        $stmt->bind_param("i", $feature_id);
        $stmt->execute();
        
        // (Note: The next full-page Save Order will fix any gaps like '1, 3, 4')
        logAdminAction($conn, 'REMOVE_FEATURED', "Removed featured entry ID $feature_id");
        echo json_encode(['success' => true]);

    // ==========================================
    // 3. MASS REMOVE ACTION (NEW)
    // ==========================================
    } elseif ($action === 'mass_delete') {
        $ids_json = $_POST['feature_ids'];
        $ids_array = json_decode($ids_json, true);

        if (!is_array($ids_array) || empty($ids_array)) {
            echo json_encode(['success' => false, 'error' => 'No items selected.']);
            exit;
        }

        $safe_ids = array_map('intval', $ids_array);
        $ids_string = implode(',', $safe_ids);

        $conn->query("DELETE FROM featured_games WHERE id IN ($ids_string)");
        
        logAdminAction($conn, 'MASS_REMOVE_FEATURED', "Removed " . count($safe_ids) . " featured entries.");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }

} catch (Exception $e) {
    logSystemError($conn, "Process Featured Error ($action): " . $e->getMessage(), 'DATABASE_ERROR');
    echo json_encode(['success' => false, 'error' => 'A database error occurred.']);
}