<?php
// ajax_search.php
// This file runs quietly in the background, fetches 3 games, and returns them as JSON.
require_once 'config/db.php';

// Tell the browser to expect JSON data
header('Content-Type: application/json');

// Grab the search query securely
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Ensure we don't waste database resources on 1-letter searches
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Add wildcards for the SQL LIKE statement
    $searchTerm = '%' . $query . '%';
    
    // We only want the top 3 best matching games, sorted by Metascore to show high-quality results
    $stmt = $conn->prepare("SELECT id, title, cover_image FROM games WHERE title LIKE ? ORDER BY metascore DESC LIMIT 3");
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();

    $games = [];
    while ($row = $result->fetch_assoc()) {
        $games[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            // Ensure cover image defaults securely if somehow null
            'cover_image' => !empty($row['cover_image']) ? $row['cover_image'] : 'assets/images/placeholder.png'
        ];
    }

    // Output the array as JSON for the Javascript to read
    echo json_encode($games);

    $stmt->close();
} catch (Exception $e) {
    // If the database fails, return an empty array so the frontend doesn't crash
    echo json_encode([]); 
}

$conn->close();
?>