<?php
// admin/process_user.php
require_once 'includes/auth.php'; 
require_once '../config/db.php'; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$action = $_POST['action'] ?? '';
$my_role = (int)$_SESSION['role_id'];

// Get target user's current role to prevent staff-on-staff attacks
$target_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$target_role = 5; // Default to normal user

if ($target_user_id > 0) {
    $t_stmt = $conn->prepare("SELECT role_id FROM users WHERE id = ?");
    $t_stmt->bind_param("i", $target_user_id);
    $t_stmt->execute();
    $t_res = $t_stmt->get_result()->fetch_assoc();
    if ($t_res) $target_role = (int)$t_res['role_id'];
}

// --- 1. CHANGE ROLE ---
if ($action === 'change_role') {
    if (!in_array($my_role, [1, 2])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized to change roles.']);
        exit;
    }

    $new_role_id = (int)$_POST['role_id'];
    
    // Editors cannot touch Admins, Editors, or Mods
    if ($my_role == 2 && in_array($target_role, [1, 2, 3])) {
        echo json_encode(['success' => false, 'error' => 'Editors cannot modify staff roles.']);
        exit;
    }

    if ($my_role == 2 && !in_array($new_role_id, [4, 5])) {
        echo json_encode(['success' => false, 'error' => 'Editors can only promote to Critic.']);
        exit;
    }

    if ($target_user_id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'error' => 'You cannot change your own role here.']);
        exit;
    }

    $conn->query("UPDATE users SET role_id = $new_role_id WHERE id = $target_user_id");

    if (isset($_POST['reset_strikes']) && $_POST['reset_strikes'] == 1) {
        $conn->query("UPDATE users SET false_report_strikes = 0, shadowbanned_reports = 0 WHERE id = $target_user_id");
    }
    echo json_encode(['success' => true]);

// --- 2. BAN USER ---
} elseif ($action === 'ban') {
    if (!in_array($my_role, [1, 2, 3])) { // Admins, Editors, and Mods can ban
        echo json_encode(['success' => false, 'error' => 'Unauthorized to ban.']);
        exit;
    }

    // Editors and Mods CANNOT ban Admins, Editors, or Mods
    if (($my_role == 2 || $my_role == 3) && in_array($target_role, [1, 2, 3])) {
        echo json_encode(['success' => false, 'error' => 'You do not have permission to ban staff members.']);
        exit;
    }

    $duration = $_POST['duration'] ?? '24h';
    $ban_date = 'NULL';
    if ($duration === '24h') $ban_date = "DATE_ADD(NOW(), INTERVAL 1 DAY)";
    elseif ($duration === '7d') $ban_date = "DATE_ADD(NOW(), INTERVAL 7 DAY)";
    elseif ($duration === 'perm') $ban_date = "'9999-12-31 23:59:59'";

    $conn->query("UPDATE users SET is_banned = 1, ban_expires_at = $ban_date WHERE id = $target_user_id");
    echo json_encode(['success' => true]);

// --- 3. UNBAN USER ---
} elseif ($action === 'unban') {
    if (!in_array($my_role, [1, 2, 3])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized to unban.']);
        exit;
    }

    if (($my_role == 2 || $my_role == 3) && in_array($target_role, [1, 2, 3])) {
        echo json_encode(['success' => false, 'error' => 'You do not have permission to unban staff members.']);
        exit;
    }

    $conn->query("UPDATE users SET is_banned = 0, ban_expires_at = NULL WHERE id = $target_user_id");
    echo json_encode(['success' => true]);

// --- 4. MASS DELETE ---
} elseif ($action === 'mass_delete') {
    if ($my_role != 1) { 
        echo json_encode(['success' => false, 'error' => 'Only Super Admins can mass delete.']);
        exit;
    }

    $user_ids_json = $_POST['user_ids'];
    $selected_ids = json_decode($user_ids_json, true);

    if (!is_array($selected_ids) || empty($selected_ids)) {
        echo json_encode(['success' => false, 'error' => 'No users selected.']);
        exit;
    }

    $safe_ids = array_map('intval', $selected_ids);
    $ids_string = implode(',', $safe_ids);

    $conn->query("DELETE FROM users WHERE id IN ($ids_string)");
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Unknown action.']);
}