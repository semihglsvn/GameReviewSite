<?php
// admin/process_report_action.php
require_once 'includes/auth.php'; 
require_once '../config/db.php'; 

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // THE GIANT SAFETY NET
    try {
        // ==========================================
        // 1. MASS DISMISS ACTION
        // ==========================================
        if ($action === 'mass_dismiss') {
            $report_ids_json = $_POST['report_ids'];
            $selected_ids = json_decode($report_ids_json, true);

            if (!is_array($selected_ids) || empty($selected_ids)) {
                echo json_encode(['success' => false, 'error' => 'No reports selected.']);
                exit;
            }

            foreach ($selected_ids as $req_report_id) {
                $req_report_id = (int)$req_report_id;

                $r_stmt = $conn->prepare("SELECT reporter_id, review_id FROM reports WHERE id = ?");
                $r_stmt->bind_param("i", $req_report_id);
                $r_stmt->execute();
                $rep_data = $r_stmt->get_result()->fetch_assoc();

                if ($rep_data) {
                    $rev_id = $rep_data['review_id'];
                    $rep_id = $rep_data['reporter_id'];

                    $conn->query("UPDATE reports SET status = 'ignored' WHERE review_id = $rev_id AND status = 'pending'");
                    $conn->query("UPDATE reviews SET mod_cleared = 1 WHERE id = $rev_id");
                    $conn->query("UPDATE users SET false_report_strikes = false_report_strikes + 1 WHERE id = $rep_id");
                    $conn->query("UPDATE users SET shadowbanned_reports = 1 WHERE id = $rep_id AND false_report_strikes >= 10");
                }
            }

            logAdminAction($conn, 'MASS_DISMISS_REPORTS', "Mass dismissed " . count($selected_ids) . " reports.");
            echo json_encode(['success' => true]);
            exit; 
        }

        // ==========================================
        // 2. SINGLE DISMISS OR BAN ACTION
        // ==========================================
        $report_id = isset($_POST['report_id']) ? (int)$_POST['report_id'] : 0;

        $r_stmt = $conn->prepare("SELECT reporter_id, review_id FROM reports WHERE id = ?");
        $r_stmt->bind_param("i", $report_id);
        $r_stmt->execute();
        $report = $r_stmt->get_result()->fetch_assoc();

        if (!$report) {
            echo json_encode(['success' => false, 'error' => 'Report not found.']);
            exit;
        }

        $review_id = $report['review_id'];
        $reporter_id = $report['reporter_id'];

        if ($action === 'dismiss') {
            $conn->query("UPDATE reports SET status = 'ignored' WHERE review_id = $review_id AND status = 'pending'");
            $conn->query("UPDATE reviews SET mod_cleared = 1 WHERE id = $review_id");
            $conn->query("UPDATE users SET false_report_strikes = false_report_strikes + 1 WHERE id = $reporter_id");
            $conn->query("UPDATE users SET shadowbanned_reports = 1 WHERE id = $reporter_id AND false_report_strikes >= 10");
            
            logAdminAction($conn, 'DISMISS_REPORT', "Dismissed report ID $report_id for Review ID $review_id and granted immunity to that review. Reporter ID $reporter_id");
            echo json_encode(['success' => true]);
            
        } elseif ($action === 'ban') {
            $author_id = (int)$_POST['author_id'];
            $duration = $_POST['duration'];

            $conn->query("UPDATE reports SET status = 'reviewed' WHERE review_id = $review_id");
            $conn->query("DELETE FROM reviews WHERE id = $review_id");

            $ban_date = 'NULL';
            if ($duration === '24h') $ban_date = "DATE_ADD(NOW(), INTERVAL 1 DAY)";
            elseif ($duration === '7d') $ban_date = "DATE_ADD(NOW(), INTERVAL 7 DAY)";
            elseif ($duration === 'perm') $ban_date = "'9999-12-31 23:59:59'";

            $conn->query("UPDATE users SET is_banned = 1, ban_expires_at = $ban_date WHERE id = $author_id");
            $conn->query("UPDATE users SET false_report_strikes = 0 WHERE id = $reporter_id");
            
            logAdminAction($conn, 'BAN_FROM_REPORT', "Banned author ID $author_id for $duration due to Report ID $report_id. Deleted Review ID $review_id");  
            echo json_encode(['success' => true]);
        }

    } catch (Exception $e) {
        // CATCHES ANY FATAL SQL ERROR IN THIS FILE
        logSystemError($conn, "Process Report Error ($action): " . $e->getMessage(), 'DATABASE_ERROR');
        echo json_encode(['success' => false, 'error' => 'A database error occurred.']);
    }
}