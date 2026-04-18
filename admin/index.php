<?php
// admin/index.php

// 1. MUST BE FIRST: Protect this page
require_once 'includes/auth.php'; 

// 2. Connect to Database
require_once '../config/db.php'; // Adjust if your path is different

// 3. Run Statistical Queries (Requirement #14)
// Total Users
$users_query = $conn->query("SELECT COUNT(id) as total FROM users");
$total_users = $users_query->fetch_assoc()['total'];

// New Reviews (Let's count reviews from the last 7 days)
$reviews_query = $conn->query("SELECT COUNT(id) as total FROM reviews WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$recent_reviews = $reviews_query->fetch_assoc()['total'];

// Pending Reports
$reports_query = $conn->query("SELECT COUNT(id) as total FROM reports WHERE status = 'pending'");
$pending_reports = $reports_query->fetch_assoc()['total'];

// Total Games
$games_query = $conn->query("SELECT COUNT(id) as total FROM games");
$total_games = $games_query->fetch_assoc()['total'];

// --- REAL SYSTEM STATUS METRICS ---

// 1. Database Size (Querying information_schema to get size in MB)
$db_size_query = $conn->query("
    SELECT SUM(data_length + index_length) / 1024 / 1024 AS db_size_mb 
    FROM information_schema.TABLES 
    WHERE table_schema = DATABASE()
");
$db_size = round($db_size_query->fetch_assoc()['db_size_mb'], 2);

// 2. Server Disk Space (Using PHP's built-in disk space functions)
// Note: "/" checks the root directory of the server. Adjust to "C:" if on Windows.
$disk_free = disk_free_space("/");
$disk_total = disk_total_space("/");
if ($disk_free !== false && $disk_total !== false) {
    $disk_used = $disk_total - $disk_free;
    $disk_percent = round(($disk_used / $disk_total) * 100, 1);
    // Convert bytes to GB
    $disk_free_gb = round($disk_free / 1073741824, 2); 
    $disk_total_gb = round($disk_total / 1073741824, 2);
    $storage_text = "{$disk_percent}% Used ({$disk_free_gb} GB free)";
    $storage_color = ($disk_percent > 90) ? "red" : "green";
} else {
    $storage_text = "Unable to read disk";
    $storage_color = "orange";
}

// 3. System Logs (Checking if there are any critical errors in your system_logs table)
$logs_query = $conn->query("SELECT COUNT(id) as total FROM system_logs");
$total_logs = $logs_query->fetch_assoc()['total'];
$log_status = ($total_logs > 0) ? "{$total_logs} Errors Logged" : "Clean";
$log_color = ($total_logs > 0) ? "red" : "green";

// Check database connection status for the table below
$db_status = ($conn->ping()) ? "Online" : "Offline";

// Now include your layout files
include 'includes/admin_header.php'; 
include 'includes/admin_sidebar.php'; 
?>

<main class="admin-content">
    
    <div class="admin-header">
        <h2>Dashboard Overview</h2>
        <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-number"><?php echo number_format($total_users); ?></span>
            <span class="stat-label">Total Users</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo number_format($recent_reviews); ?></span>
            <span class="stat-label">New Reviews (7 Days)</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo number_format($pending_reports); ?></span>
            <span class="stat-label">Pending Reports</span>
        </div>
        <div class="stat-card">
            <span class="stat-number"><?php echo number_format($total_games); ?></span>
            <span class="stat-label">Games Listed</span>
        </div>
    </div>

<div class="table-container">
        <h3 style="margin-top:0;">System Status</h3>
        <table>
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Status / Metric</th>
                    <th>Last Check</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Database (MariaDB)</strong></td>
                    <td>
                        <?php if($db_status == "Online"): ?>
                            <span style="color:green; font-weight:bold;">Online</span> 
                            <span style="color:#666; font-size: 13px;">(Size: <?php echo $db_size; ?> MB)</span>
                        <?php else: ?>
                            <span style="color:red; font-weight:bold;">Offline</span>
                        <?php endif; ?>
                    </td>
                    <td>Just now</td>
                </tr>
                <tr>
                    <td><strong>File Storage (Server)</strong></td>
                    <td><span style="color:<?php echo $storage_color; ?>; font-weight:bold;"><?php echo $storage_text; ?></span></td>
                    <td>Just now</td>
                </tr>
                <tr>
                    <td><strong>System Error Logs</strong></td>
                    <td><span style="color:<?php echo $log_color; ?>; font-weight:bold;"><?php echo $log_status; ?></span></td>
                    <td>Just now</td>
                </tr>
            </tbody>
        </table>
    </div>
</main>

<?php include 'includes/admin_footer.php'; ?>