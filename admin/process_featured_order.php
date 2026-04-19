<?php
// admin/process_featured_order.php
require_once 'includes/auth.php';
require_once '../config/db.php';

header('Content-Type: application/json');

// Only Admins and Editors can curate content
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($_SESSION['role_id'], [1, 2])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Receive the entire order as a JSON string of game_ids
$order_json = $_POST['order'] ?? '';
$ordered_game_ids = json_decode($order_json, true);

if (!is_array($ordered_game_ids)) {
    echo json_encode(['success' => false, 'error' => 'Invalid order data received.']);
    exit;
}

// A new list cannot exceed 16
if (count($ordered_game_ids) > 16) {
     echo json_encode(['success' => false, 'error' => 'Slider list cannot exceed 16 games.']);
     exit;
}

try {
    // 1. Log the attempt
    logAdminAction($conn, 'UPDATE_FEATURED_ORDER_START', "User ID {$_SESSION['user_id']} starting featured order update.");

    // ==========================================
    // THE ULTIMATE SAFE TRANSACTION
    // ==========================================
    // We wipe the existing lookups and rebuild them perfectly based on the new order.
    $conn->begin_transaction();

    // 2. Securely get existing feature settings (like custom banners) 
    // to preserve them during the wipe.
    $old_settings_query = $conn->query("SELECT game_id, custom_banner FROM featured_games");
    $preserved_banners = [];
    while ($row = $old_settings_query->fetch_assoc()) {
        if ($row['custom_banner']) {
            $preserved_banners[$row['game_id']] = $row['custom_banner'];
        }
    }

    // 3. Clear the whole featured list
    $conn->query("DELETE FROM featured_games");

    // 4. Rebuild the list perfectly with 1-based order
    $stmt = $conn->prepare("INSERT INTO featured_games (game_id, display_order, custom_banner) VALUES (?, ?, ?)");
    
    foreach ($ordered_game_ids as $index => $game_id) {
        $game_id = (int)$game_id;
        $order = $index + 1; // 1-based ordering
        
        // Restore custom banner if it existed for this game
        $banner = $preserved_banners[$game_id] ?? null;

        $stmt->bind_param("iis", $game_id, $order, $banner);
        $stmt->execute();
    }

    $conn->commit();
    // ==========================================

    logAdminAction($conn, 'UPDATE_FEATURED_ORDER_SUCCESS', "Successfully updated featured games order (Count: " . count($ordered_game_ids) . ").");
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Critical failure - rollback everything
    $conn->rollback();
    logSystemError($conn, "CRITICAL: Featured Order Update Failed: " . $e->getMessage(), 'TRANSACTION_FAILURE');
    echo json_encode(['success' => false, 'error' => 'A critical database error occurred while saving the new order. Rollback initiated.']);
}