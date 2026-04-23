<?php
// api/ajax_load_slider.php
require_once __DIR__ . '/../config/db.php';

// STRICT CACHE BUSTING: Forces the browser to fetch fresh scores every time
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header('Content-Type: application/json');

$type = $_GET['type'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$page = (int)($_GET['page'] ?? 1);

// We fetch 12 games at a time (3 slides of 4 games)
$limit = 12; 
$offset = ($page - 1) * $limit;

// Security check: We only do Genres now
if ($id <= 0 || $type !== 'genre') {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    // We explicitly GROUP BY every selected column to prevent strict-mode SQL failures
    $stmt = $conn->prepare("
        SELECT g.id, g.title, g.cover_image, g.metascore, 
               GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as platform_names
        FROM games g
        JOIN game_genres gg ON g.id = gg.game_id
        LEFT JOIN game_platforms gp ON g.id = gp.game_id
        LEFT JOIN platforms p ON gp.platform_id = p.id
        WHERE gg.genre_id = ?
        GROUP BY g.id, g.title, g.cover_image, g.metascore
        ORDER BY g.metascore DESC, g.id DESC
        LIMIT ? OFFSET ?
    ");

    $stmt->bind_param("iii", $id, $limit, $offset);
    $stmt->execute();
    $games = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // If we fetched exactly 12 games, there is likely a next page.
    $has_more = count($games) === $limit;

    // Pad the array so the UI grid doesn't break if we get 2 games instead of 4
    $remainder = count($games) % 4;
    if ($remainder > 0 && count($games) > 0) {
        for ($i = 0; $i < (4 - $remainder); $i++) {
            $games[] = ['is_empty' => true];
        }
    }

    // Group into slides of 4
    $slides = array_chunk($games, 4);

    echo json_encode([
        'success' => true,
        'has_more' => $has_more,
        'slides' => $slides
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>