<?php
require_once 'includes/auth.php';
require_once '../config/db.php';
require_once 'includes/logger.php'; // Include your logger!

$current_role = $_SESSION['role_id'] ?? 5;
if ($current_role != 1 && $current_role != 2) {
    // Log the unauthorized attempt before killing the script
    logSystemError($conn, "Unauthorized export attempt by User ID: " . $_SESSION['user_id'], "SECURITY_WARNING");
    die("Yetkisiz erişim.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['export_type'] ?? '';
    
    // Log the successful export action
    logAdminAction($conn, 'DATA_EXPORT', "Exported {$type} table to CSV.");

    $filename = $type . "_export_" . date('Y-m-d') . ".csv";
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

    if ($type === 'users') {
        fputcsv($output, ['ID', 'Kullanıcı Adı', 'E-posta', 'Rol ID', 'Kayıt Tarihi']);
        $result = $conn->query("SELECT id, username, email, role_id, created_at FROM users");
        while ($row = $result->fetch_assoc()) fputcsv($output, $row);
        
    } elseif ($type === 'games') {
        fputcsv($output, ['ID', 'Oyun Adı', 'Metascore', 'Çıkış Tarihi']);
        $result = $conn->query("SELECT id, title, metascore, release_date FROM games");
        while ($row = $result->fetch_assoc()) fputcsv($output, $row);
        
    } elseif ($type === 'reports') {
        fputcsv($output, ['Rapor ID', 'İnceleme ID', 'Raporlayan Kullanıcı ID', 'Sebep', 'Tarih']);
        $result = $conn->query("SELECT id, review_id, user_id, reason, created_at FROM reports");
        if($result) { while ($row = $result->fetch_assoc()) fputcsv($output, $row); }
    }
    
    fclose($output);
    exit;
}