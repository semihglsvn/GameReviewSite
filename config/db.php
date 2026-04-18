<?php
// config/db.php

$host = 'localhost';
$dbname = 'gamejoint_db';
$username = 'semih'; 
$password = '';     

// Tell mysqli to throw exceptions on errors (Req #3: Strict error handling for Prepared Statements)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Attempt connection
    $conn = new mysqli($host, $username, $password, $dbname);
    
    // Req #4: Force UTF-8 Encoding in the connection to support Turkish characters perfectly
    $conn->set_charset("utf8mb4");

} catch (mysqli_sql_exception $e) {
    // Req #18: Log fatal database connection errors securely (IP, Date, Time)
    $errorMsg = "DB Connection Failed: " . $e->getMessage();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $date = date('Y-m-d H:i:s');
    
    // Since the database is down, we must log to a physical text file securely
    $logEntry = "[$date] IP: $ip - ERROR: $errorMsg" . PHP_EOL;
    
    // Write to error_log.txt in the root directory
    file_put_contents(__DIR__ . '/../error_log.txt', $logEntry, FILE_APPEND);
    
    // Req #19: Show a user-friendly message in English, hiding technical details
    die("Our system is experiencing a temporary technical issue. Please try again later.");
}

// ========================================================
// GLOBAL EXCEPTION HANDLER (Catches runtime errors across the whole site)
// ========================================================
function siteWideExceptionHandler($exception) {
    global $conn;
    
    // 1. Format a clean error message detailing exactly where it broke
    $error_msg = $exception->getMessage() . " in " . basename($exception->getFile()) . " on line " . $exception->getLine();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $error_type = 'SITE_ERROR';
    
    // 2. Try to log it to our system_logs table
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        try {
            $stmt = $conn->prepare("INSERT INTO system_logs (error_type, error_message, ip_address) VALUES (?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sss", $error_type, $error_msg, $ip_address);
                $stmt->execute();
            }
        } catch (Exception $log_e) {
            // Failsafe: if writing to the DB log fails, write to the text file
            file_put_contents(__DIR__ . '/../error_log.txt', "[" . date('Y-m-d H:i:s') . "] DB LOG FAILED: " . $log_e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    } else {
        // 3. Fallback: If DB is unavailable, write to error_log.txt
        file_put_contents(__DIR__ . '/../error_log.txt', "[" . date('Y-m-d H:i:s') . "] CRITICAL: " . $error_msg . PHP_EOL, FILE_APPEND);
    }
    
    // 4. Handle the user-facing response gracefully
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Reply with JSON for AJAX requests
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'A system error occurred. Admins have been notified.']);
    } else {
        // Reply with a safe HTML screen for standard page loads
        http_response_code(500);
        echo "<div style='text-align:center; margin-top:100px; font-family:sans-serif;'>";
        echo "<h2 style='color:#e74c3c;'>Oops! Something went wrong.</h2>";
        echo "<p>A critical system error occurred. Our admin team has been automatically notified and is looking into it.</p>";
        echo "<a href='/GameReviewSite/index.php'>Return to Homepage</a>";
        echo "</div>";
    }
    
    exit;
}

// Tell PHP to use our custom function whenever a Fatal Exception occurs anywhere on the site
set_exception_handler('siteWideExceptionHandler');
?>