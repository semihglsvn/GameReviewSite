<?php
// admin/process_menus.php
require_once 'includes/auth.php';
require_once '../config/db.php';

header('Content-Type: application/json');

// STRICT SECURITY
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    // ---------------------------------------------------------
    // 1. ADD NEW MENU
    // ---------------------------------------------------------
    if ($action === 'add') {
        $title = trim($_POST['title']);
        $url = trim($_POST['url']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($title) || empty($url)) {
            echo json_encode(['success' => false, 'error' => 'Title and URL are required.']);
            exit;
        }

        // Auto-assign the next sort order (put it at the bottom of the list)
        $order_query = $conn->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM menus");
        $next_order = $order_query->fetch_row()[0];

        $stmt = $conn->prepare("INSERT INTO menus (title, url, sort_order, is_active) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $title, $url, $next_order, $is_active);
        
        if ($stmt->execute()) {
            logAdminAction($conn, 'ADD_MENU', "Added nav link: $title");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error.']);
        }
    }

    // ---------------------------------------------------------
    // 2. DELETE MENU
    // ---------------------------------------------------------
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Fetch title for the log before deleting
        $title_stmt = $conn->query("SELECT title FROM menus WHERE id = $id");
        $title = $title_stmt->fetch_assoc()['title'] ?? 'Unknown';

        $stmt = $conn->prepare("DELETE FROM menus WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            logAdminAction($conn, 'DELETE_MENU', "Deleted nav link: $title");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to delete.']);
        }
    }

    // ---------------------------------------------------------
    // 3. TOGGLE VISIBILITY
    // ---------------------------------------------------------
    elseif ($action === 'toggle') {
        $id = (int)$_POST['id'];
        
        // Flips 1 to 0, or 0 to 1
        $stmt = $conn->prepare("UPDATE menus SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            logAdminAction($conn, 'TOGGLE_MENU', "Toggled visibility for menu ID: $id");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to toggle.']);
        }
    }

    // ---------------------------------------------------------
    // 4. UPDATE SORT ORDER (Bulk Update)
    // ---------------------------------------------------------
    elseif ($action === 'update_order') {
        $orders = $_POST['order'] ?? [];
        
        // Prepare a single statement to loop through
        $stmt = $conn->prepare("UPDATE menus SET sort_order = ? WHERE id = ?");
        
        foreach ($orders as $id => $sort_order) {
            $id = (int)$id;
            $sort_order = (int)$sort_order;
            $stmt->bind_param("ii", $sort_order, $id);
            $stmt->execute();
        }
        
        logAdminAction($conn, 'REORDER_MENUS', "Updated navigation display order.");
        echo json_encode(['success' => true]);
    }

    else {
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
    }

} catch (Exception $e) {
    logSystemError($conn, "Process Menus Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'System exception occurred.']);
}