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
?>