<?php
// admin/process_settings.php
require_once 'includes/auth.php';
require_once '../config/db.php';

header('Content-Type: application/json');

// STRICT SECURITY
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['role_id'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Sanitize Inputs
    $site_title = trim($_POST['site_title'] ?? '');
    $site_desc = trim($_POST['site_description'] ?? '');
    $logo = trim($_POST['site_logo'] ?? '');
    $footer = trim($_POST['footer_about_text'] ?? '');
    $copyright = trim($_POST['copyright_text'] ?? '');
    $email = trim($_POST['contact_email'] ?? '');
    $facebook = trim($_POST['facebook_url'] ?? '');
    $twitter = trim($_POST['twitter_url'] ?? '');

    // Basic validation
    if (empty($site_title)) {
        echo json_encode(['success' => false, 'error' => 'Site Title is required.']);
        exit;
    }

    // Update Query (Assuming ID 1 is always the settings row)
    $stmt = $conn->prepare("
        UPDATE settings SET 
            site_title = ?, 
            site_description = ?, 
            site_logo = ?, 
            footer_about_text = ?, 
            copyright_text = ?, 
            contact_email = ?, 
            facebook_url = ?, 
            twitter_url = ?
        WHERE id = 1
    ");

    $stmt->bind_param("ssssssss", 
        $site_title, $site_desc, $logo, $footer, 
        $copyright, $email, $facebook, $twitter
    );

    if ($stmt->execute()) {
        logAdminAction($conn, 'UPDATE_SETTINGS', "Updated global site settings.");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database execution failed.']);
    }

} catch (Exception $e) {
    logSystemError($conn, "Process Settings Error: " . $e->getMessage(), 'DATABASE_ERROR');
    echo json_encode(['success' => false, 'error' => 'A system error occurred.']);
}