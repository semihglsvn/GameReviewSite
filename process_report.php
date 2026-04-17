<?php
// process_report.php
require_once __DIR__ . '/config/db.php';
session_start();
header('Content-Type: application/json');

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in to report.']);
    exit;
}

$reporter_id = (int)$_SESSION['user_id'];

// Check if user is banned
$ban_stmt = $conn->prepare("SELECT is_banned FROM users WHERE id = ?");
$ban_stmt->bind_param("i", $reporter_id);
$ban_stmt->execute();
$user_status = $ban_stmt->get_result()->fetch_assoc();

if ($user_status && $user_status['is_banned'] == 1) {
    echo json_encode(['success' => false, 'error' => 'Your account has been banned. You cannot submit reports.']);
    exit;
}

// Process the report
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = isset($_POST['review_id']) ? (int)$_POST['review_id'] : 0;
    
    // Ensure reasons were selected
    if (empty($_POST['reasons']) || !is_array($_POST['reasons'])) {
        echo json_encode(['success' => false, 'error' => 'Please select at least one reason.']);
        exit;
    }
    
    // Convert array of checkboxes into a comma-separated string
    $reasons_str = implode(', ', array_map('htmlspecialchars', $_POST['reasons']));

    if ($review_id > 0) {
        // Prevent duplicate spam reporting by the same user on the same review
        $chk = $conn->prepare("SELECT id FROM reports WHERE reporter_id = ? AND review_id = ? AND status = 'pending'");
        $chk->bind_param("ii", $reporter_id, $review_id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'You have already reported this review.']);
            exit;
        }

        // Insert report
// Find out if the reporter is shadowbanned OR if the review has Mod Immunity
        $chk_shadow = $conn->query("SELECT shadowbanned_reports FROM users WHERE id = $reporter_id")->fetch_assoc();
        $chk_review = $conn->query("SELECT mod_cleared FROM reviews WHERE id = $review_id")->fetch_assoc();

        if (($chk_shadow && $chk_shadow['shadowbanned_reports'] == 1) || ($chk_review && $chk_review['mod_cleared'] == 1)) {
            // TRAP TRIGGERED: 
            // Either the user is a spammer, OR a mod already cleared this review.
            // We do not insert anything into the database, keeping it perfectly clean.
            
            // Tell the user it worked perfectly so they don't get suspicious
            echo json_encode(['success' => true]); 
            exit;
        } else {
            // Normal user & Un-moderated review: Insert as pending
            $ins = $conn->prepare("INSERT INTO reports (reporter_id, review_id, reasons) VALUES (?, ?, ?)");
            $ins->bind_param("iis", $reporter_id, $review_id, $reasons_str);
            
            if ($ins->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error.']);
            }
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid review ID.']);
    }
}