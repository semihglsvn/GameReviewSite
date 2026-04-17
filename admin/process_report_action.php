<?php
// admin/process_report_action.php
require_once 'includes/auth.php'; 
require_once '../config/db.php'; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_id = (int)$_POST['report_id'];
    $action = $_POST['action']; // 'dismiss' or 'ban'

    // Get the report details to find the reporter and review ID
    $r_stmt = $conn->prepare("SELECT reporter_id, review_id FROM reports WHERE id = ?");
    $r_stmt->bind_param("i", $report_id);
    $r_stmt->execute();
    $report = $r_stmt->get_result()->fetch_assoc();

    if (!$report) {
        echo json_encode(['success' => false, 'error' => 'Report not found.']);
        exit;
    }

    if ($action === 'dismiss') {
        $review_id = $report['review_id'];
        $reporter_id = $report['reporter_id'];

        // 1. Mark ALL pending reports for this specific review as ignored (clears the queue instantly)
        $conn->query("UPDATE reports SET status = 'ignored' WHERE review_id = $review_id AND status = 'pending'");

        // 2. Grant the review "Mod Immunity" so it never enters the queue again
        $conn->query("UPDATE reviews SET mod_cleared = 1 WHERE id = $review_id");

        // 3. Add a strike to the reporter who made THIS specific report
        $conn->query("UPDATE users SET false_report_strikes = false_report_strikes + 1 WHERE id = $reporter_id");

        // 4. Check if they hit 10 strikes, if so, shadowban them
        $conn->query("UPDATE users SET shadowbanned_reports = 1 WHERE id = $reporter_id AND false_report_strikes >= 10");

        echo json_encode(['success' => true]);
        
    } elseif ($action === 'ban') {
        $author_id = (int)$_POST['author_id'];
        $duration = $_POST['duration'];
        $review_id = $report['review_id'];

        // 1. Mark ALL reports attached to this review as reviewed
        $conn->query("UPDATE reports SET status = 'reviewed' WHERE review_id = $review_id");

        // 2. Delete the offending review completely
        $conn->query("DELETE FROM reviews WHERE id = $review_id");

        // 3. Apply the ban to the author
        $ban_date = 'NULL';
        if ($duration === '24h') $ban_date = "DATE_ADD(NOW(), INTERVAL 1 DAY)";
        elseif ($duration === '7d') $ban_date = "DATE_ADD(NOW(), INTERVAL 7 DAY)";
        elseif ($duration === 'perm') $ban_date = "'9999-12-31 23:59:59'";

        $conn->query("UPDATE users SET is_banned = 1, ban_expires_at = $ban_date WHERE id = $author_id");

        // 4. Reward the reporter by resetting their strikes to 0!
        $reporter_id = $report['reporter_id'];
        $conn->query("UPDATE users SET false_report_strikes = 0 WHERE id = $reporter_id");

        echo json_encode(['success' => true]);
    }
}